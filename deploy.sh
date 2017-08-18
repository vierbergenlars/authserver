#!/bin/bash
set -e # Quit the script on error
cd "$( dirname "${BASH_SOURCE[0]}" )" # cd to the directory containg the script
trap '[[ -e "maintenance" ]] && printf "\x1b[30;43mMaintenance mode is still enabled\x1b[0m\n"' EXIT
ARGS=$(getopt -o nse:luh --long no-pull,shell,env:,skip-maintenance,lock-maintenance,unlock-maintenance,help,commit:,revert -n "deploy.sh" -- "$@")
no_pull=0
no_git=0
shell=0
skip_maintenance=0
target_commit="origin/HEAD"
[[ -z "$SYMFONY_ENV" ]] && [[ -e ".staging" ]] && export SYMFONY_ENV="staging" # If no environment is set and staging flag exists, use staging
[[ -z "$SYMFONY_ENV" ]] && export SYMFONY_ENV="prod" # If no environment is set, use production
eval set -- "$ARGS"
show_help() { cat <<EOL
deploy.sh - A simple application deployment tool

Usage:
    bash deploy.sh --[un]lock-maintenance
    bash deploy.sh --shell [--env <env>] [--skip-maintenance]
    bash deploy.sh [--no-pull] [--env <env>] [--revert|--commit <commit>] [--skip-maintenance]

Options:
    -n, --no-pull               Do not pull a new version from the git repository, re-deploy the currently checked out version.
    -s, --shell                 Start a new maintenance shell, with the right environment set up.
    -e <env>, --env <env>       Change the default symfony environment to this one.
    -l, --lock-maintenance      Enable maintenance mode, and do not disable it until the unlock command is given.
    -u, --unlock-maintenance    Disable maintenance mode.
    --skip-maintenance          Perform an action without enabling maintenance mode.
    --commit <commit>           Switches application to this commit. Defaults to origin/HEAD
    --revert                    Reverts the application to the previous version. (sets --commit HEAD@{1} --no-pull)

When invoked without any parameters, it executes the following sequence:
 * Set up deployment environment
 * Enable maintenance mode
 * Fetches changes from the remote repository
 * Check out the requested commit
 * Run application update scripts
 * Disable maintenance mode

The deployment environment can be overridden with --env <environment>;
Pulling the latest version can be disabled with --no-pull;
Enabling and disabling maintenance mode can be skipped with --skip-maintenance.

The application can be forced in maintenance mode without deployment with --lock-maintenance;
this maintenance must be disabled with --unlock-maintenance, as it will not be disabled by a simple deploy.

Finally, --shell can be used to get a shell in maintenance mode that automatically disables maintenance on exit,
or together with --skip-maintenance to only set up the right environment variables for a deployment.
EOL
}
while true; do
    case "$1" in
        -n|--no-pull)
            no_pull=1
            shift
            ;;
        -s|--shell)
            shell=1
            shift
            ;;
        -e|--env)
            export SYMFONY_ENV="$2"
            shift 2
            ;;
        --commit)
            [[ "$target_commit" != "origin/HEAD" ]] && (echo "--commit and --revert cannot be used together" && show_help && exit 1)
            target_commit=$(git rev-parse --revs-only "$2" --)
            shift 2
            ;;
        --revert)
            [[ "$target_commit" != "origin/HEAD" ]] && (echo "--commit and --revert cannot be used together" && show_help && exit 1)
            target_commit=$(git rev-parse --revs-only 'HEAD@{1}' --)
            no_pull=1
            shift
            ;;
        --skip-maintenance)
            skip_maintenance=1
            shift
            ;;
        -l|--lock-maintenance)
            touch maintenance.lock
            cp _maintenance.html maintenance
            echo "Maintenance mode locked"
            exit
            ;;
        -u|--unlock-maintenance)
            rm -f maintenance.lock maintenance
            echo "Maintenance mode unlocked"
            exit
            ;;
        --)
            shift;
            break;
            ;;
        -h|--help)
            show_help
            exit
            break
            ;;
        *)
            echo "Unknown flag $1."
            echo ""
            show_help
            exit 1
            ;;
    esac
done
if [[ ! -e "maintenance" ]] && [[ skip_maintenance -eq 0 ]]
then
    cp _maintenance.html maintenance && echo "Maintenance mode enabled"
fi
PS1="\$(basename \$(pwd))[$SYMFONY_ENV]"
[[ -e "maintenance" ]] && PS1="$PS1(maint)"
export PS1="$PS1\$ "
if [[ shell -ne 0 ]]
then
    bash --norc
else
    if [[ ! -e ".git" ]]; then
        no_git=1
        printf "\x1b[37;41mWARNING:\x1b[0m This is not a git repository. Automatic updates are disabled."
    fi
    if [[ no_git -eq 0 ]]; then
        original_commit=$(git rev-parse HEAD)
        [[ no_pull -eq 0 ]] && git fetch
        removed_migrations=$(git diff --name-status -R "$target_commit" -- app/DoctrineMigrations | grep ^D | cut -f2 | sed -r 's/^.*\/Version([0-9]+)\.php$/\1/')
        if [[ -n ${removed_migrations} ]] # There are migrations that are removed when going to this version
        then
            earliest_migration=$(echo "$removed_migrations" | sort -n | head -1) # Find the earlies migration to revert
            prev_migration=$(ls app/DoctrineMigrations/Version*.php | sed -r 's/^.*\/Version([0-9]+)\.php$/\1/' | sort | grep -C1 "$earliest_migration" | head -1) # And find the version before that
            printf "\x1b[37;41mWARNING:\x1b[0m Migrations \x1b[33m$earliest_migration\x1b[0m and later will be removed when moving to $target_commit.\n"
            php app/console doctrine:migrations:migrate --dry-run "$prev_migration"
            printf "\x1b[37;41mDANGER!\x1b[0m You are about to \x1b[32mdowngrade\x1b[0m the database to version \x1b[33m$prev_migration\x1b[0m\n"
            read -p "WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)" cont
            [[ "$cont" != "y" && "$cont" != "Y" ]] && (printf "\x1b[37;41mMigration cancelled\x1b[0m\n"; exit 1)
            php app/console doctrine:migrations:migrate "$prev_migration" -n
        fi
        git checkout "$target_commit"
    fi
    composer install --no-dev --optimize-autoloader
    npm install
    rm -rf app/cache/$SYMFONY_ENV/* # Prevent class not found errors during cache clear
    php app/console cache:clear
    php app/console theming:generate:bootstrap
    php app/console assets:install
    php app/console assetic:dump
    php app/console braincrafted:bootstrap:install
    # Only execute migrations when there are new migrations available.
    php app/console doctrine:migrations:status | grep "New Migrations:" | cut -d: -f2 |grep "^ *0" > /dev/null || \
    php app/console doctrine:migrations:migrate
    if [[ no_git -eq 0 ]]; then
        if [[ "$original_commit" == $(git rev-parse "$target_commit") ]]
        then
            printf "\x1b[30;42mRe-deploy finished\x1b[0m \x1b[30:46m$(git rev-parse --short "$original_commit")\x1b[0m\n"
        else
            printf "\x1b[30;42mDeploy finished\x1b[0m \x1b[30;46m$(git rev-parse --short "$original_commit")\x1b[0m -> \x1b[30;46m$(git rev-parse --short HEAD)\x1b[0m\n"
        fi
    else
        printf "\x1b[30;42mDeploy finished\x1b[0m\n"
    fi
fi
[[ -e "maintenance.lock" ]] && echo "Warning: maintenance mode is locked. Not disabling maintenance mode"
[[ ! -e "maintenance.lock" ]] && [[ skip_maintenance -eq 0 ]] && rm maintenance && echo "Maintenance mode disabled"
