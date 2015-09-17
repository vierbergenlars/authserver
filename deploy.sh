#!/bin/bash
set -e # Quit the script on error
cd "$( dirname "${BASH_SOURCE[0]}" )" # cd to the directory containg the script
ARGS=$(getopt -o nse:luh --long no-pull,shell,env:,skip-maintenance,lock-maintenance,unlock-maintenance,help -n "deploy.sh" -- "$@")
no_pull=0
shell=0
skip_maintenance=0
[[ -z "$SYMFONY_ENV" ]] && [[ -e ".staging" ]] && export SYMFONY_ENV="staging" # If no environment is set and staging flag exists, use staging
[[ -z "$SYMFONY_ENV" ]] && export SYMFONY_ENV="prod" # If no environment is set, use production
eval set -- "$ARGS"
show_help() { cat <<EOL
deploy.sh - A simple application deployment tool

Usage: bash deploy.sh [-l|-u|-s|-n] [-e <env>]

Options:
    -n, --no-pull               Do not pull a new version from the git repository, re-deploy the currently checked out version.
    -s, --shell                 Start a new maintenance shell, with the right environment set up.
    -e <env>, --env <env>       Change the default symfony environment to this one.
    -l, --lock-maintenance      Enable maintenance mode, and do not disable it until the unlock command is given.
    -u, --unlock-maintenance    Disable maintenance mode.
    --skip-maintenance          Perform an action without enabling maintenance mode.

When invoked without any parameters, it executes the following sequence:
 * Set up deployment environment
 * Enable maintenance mode
 * Pull the latest version of the current branch from the remote repository
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
            rm maintenance.lock
            rm maintenance
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
[[ ! -e "maintenance" ]] && [[ skip_maintenance -eq 0 ]] && cp _maintenance.html maintenance && echo "Maintenance mode enabled"
PS1="\$(basename \$(pwd))[$SYMFONY_ENV]"
[[ -e "maintenance" ]] && PS1="$PS1(maint)"
export PS1="$PS1\$ "
if [[ shell -ne 0 ]]
then
    bash --norc
else
    [[ no_pull -eq 0 ]] && (git pull --rebase || (echo "git pull --rebase failed."&&bash --norc))
    composer install --no-dev --optimize-autoloader
    npm install
    php app/console assets:install
    php app/console assetic:dump
    php app/console braincrafted:bootstrap:install
    # Only execute migrations when there are new migrations available.
    php app/console doctrine:migrations:status | grep "New Migrations:" | cut -d: -f2 |grep "^ *0" > /dev/null || \
    php app/console doctrine:migrations:migrate
fi
[[ -e "maintenance.lock" ]] && echo "Warning: maintenance mode is locked. Not disabling maintenance mode"
[[ ! -e "maintenance.lock" ]] && [[ skip_maintenance -eq 0 ]] && rm maintenance && echo "Maintenance mode disabled"
