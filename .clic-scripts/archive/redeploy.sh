#!/usr/bin/env bash
set -e
export SYMFONY_ENV=$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)
bash deploy.sh -l
if [[ "$SYMFONY_ENV" != "dev" ]]; then
    composer install --no-dev --optimize-autoloader
else
    composer install --optimize-autoloader
fi;
npm install
rm -rf app/cache/*/* # Prevent class not found errors during cache clear
php app/console cache:clear
php app/console assets:install
php app/console assetic:dump
php app/console braincrafted:bootstrap:install
# Only execute migrations when there are new migrations available.
php app/console doctrine:migrations:status | grep "New Migrations:" | cut -d: -f2 |grep "^ *0" > /dev/null || \
php app/console doctrine:migrations:migrate </dev/tty
bash deploy.sh -u
