#!/usr/bin/env bash
set -e
export SYMFONY_ENV=$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)

source .clic-scripts/maintenance.inc.sh

git fetch origin

old_stash=$(git rev-parse -q --verify refs/stash)
git stash save -q --keep-index
new_stash=$(git rev-parse -q --verify refs/stash)

git checkout origin/HEAD

if [[ "$old_stash" != "$new_stash" ]]; then
    git stash pop
fi

source .clic-scripts/deploy.inc.sh

printf "\x1b[30;42mUpdate finished\x1b[0m \x1b[30;46m$(git rev-parse --short 'HEAD@{1}')\x1b[0m -> \x1b[30;46m$(git rev-parse --short HEAD)\x1b[0m\n"
