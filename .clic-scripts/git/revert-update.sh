#!/usr/bin/env bash
set -e
export SYMFONY_ENV=$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)

target_commit=$(git rev-parse 'HEAD@{1}') # The previous HEAD

# Find database migrations that have been removed in this version
removed_migrations=$(git diff --name-status -R "$target_commit" -- app/DoctrineMigrations | grep ^D | cut -f2 | sed -r 's/^.*\/Version([0-9]+)\.php$/\1/')
if [[ -n ${removed_migrations} ]]; then
    source ../maintenance.inc.sh
    # There are migrations that are removed when going to this version
    earliest_migration=$(echo "$removed_migrations" | sort -n | head -1) # Find the earliest migration to revert
    prev_migration=$(ls app/DoctrineMigrations/Version*.php | sed -r 's/^.*\/Version([0-9]+)\.php$/\1/' | sort | grep -C1 "$earliest_migration" | head -1) # And find the version before that
    printf "\x1b[37;41mWARNING:\x1b[0m Migrations \x1b[33m$earliest_migration\x1b[0m and later will be removed when moving to $target_commit.\n"
    php app/console doctrine:migrations:migrate --dry-run "$prev_migration" -n
    printf "\x1b[37;41mDANGER!\x1b[0m You are about to \x1b[32mdowngrade\x1b[0m the database to version \x1b[33m$prev_migration\x1b[0m\n"
    read -p "WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)" cont < /dev/tty
    [[ "$cont" != "y" && "$cont" != "Y" ]] && (printf "\x1b[37;41mMigration cancelled\x1b[0m\n"; exit 1)
    php app/console doctrine:migrations:migrate "$prev_migration" -n
fi
git checkout "$target_commit"

source .clic-scripts/deploy.inc.sh

printf "\x1b[30;42mRevert finished\x1b[0m \x1b[30;46m$(git rev-parse --short 'HEAD@{1}')\x1b[0m -> \x1b[30;46m$(git rev-parse --short HEAD)\x1b[0m\n"
