#!/bin/bash
set -e # Quit the script on error
cd "$( dirname "${BASH_SOURCE[0]}" )" # cd to the directory containg the script
[[ -z "$SYMFONY_ENV" ]] && [[ -e ".staging" ]] && export SYMFONY_ENV="staging" # If no environment is set and staging flag exists, use staging
[[ -z "$SYMFONY_ENV" ]] && export SYMFONY_ENV="prod" # If no environment is set, use production
if ! [[ -e "plugins/composer.json" ]]; then
    cat > plugins/composer.json <<EOL
{
    "type": "metapackage",
    "require": {
        "vierbergenlars/authserver-installer": "^1.2.0"
    },
    "config": {
        "prepend-autoloader": false
    },
    "extra": {
        "parent-lock-file": "../composer.lock",
        "authserver-plugin-dir": "."
    }
}
EOL
    composer --working-dir=plugins install
fi;

composer --working-dir=plugins "$@"
