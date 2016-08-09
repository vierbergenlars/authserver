#!/usr/bin/env bash
set -e
export SYMFONY_ENV=$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)
source .clic-scripts/deploy.inc.sh
printf "\x1b[30;42mRe-deploy finished\x1b[0m \x1b[30:46m$(git rev-parse --short HEAD)\x1b[0m\n"
