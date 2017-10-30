#!/bin/bash
set -e # Quit the script on error
cd "$( dirname "${BASH_SOURCE[0]}" )" # cd to the directory containg the script
[[ -z "$SYMFONY_ENV" ]] && [[ -e ".staging" ]] && export SYMFONY_ENV="staging" # If no environment is set and staging flag exists, use staging
[[ -z "$SYMFONY_ENV" ]] && export SYMFONY_ENV="prod" # If no environment is set, use production

function init_composer_json {
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
}

function init_composer_lock {
    sed 's/\\/\\\\/g' > plugins/composer.lock <<EOL
{
    "_readme": [
        "This file locks the dependencies of your project to a known state",
        "Read more about it at https://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file",
        "This file is @generated automatically"
    ],
    "content-hash": "e0b456db4c0210a32f43f7fe0257655c",
    "packages": [
        {
            "name": "vierbergenlars/authserver-installer",
            "version": "v1.2.0",
            "source": {
                "type": "git",
                "url": "https://gitea.vbgn.be/authserver/authserver-installer.git",
                "reference": "10f0ed2d7e4a6b41356fbc768412f35367a28876"
            },
            "require": {
                "composer-plugin-api": "^1.1"
            },
            "require-dev": {
                "composer/composer": "^1.5"
            },
            "type": "composer-plugin",
            "extra": {
                "class": "vierbergenlars\\Authserver\\Composer\\AuthserverInstallerPlugin"
            },
            "autoload": {
                "psr-4": {
                    "vierbergenlars\\Authserver\\Composer\\": "src/"
                }
            },
            "notification-url": "https://packagist.org/downloads/",
            "license": [
                "AGPL"
            ],
            "authors": [
                {
                    "name": "Lars Vierbergen",
                    "email": "vierbergenlars@gmail.com"
                }
            ],
            "description": "Composer installer for custom Authserver plugins",
            "time": "2017-08-30T12:01:23+00:00"
        }
    ],
    "packages-dev": [],
    "aliases": [],
    "minimum-stability": "stable",
    "stability-flags": [],
    "prefer-stable": false,
    "prefer-lowest": false,
    "platform": [],
    "platform-dev": []
}
EOL
}

function composer_install {
    composer --working-dir=plugins install
}

if [[ -f "plugins/composer.json" && -f "plugins/composer.lock" && ! -d "plugins/vendor" ]]; then
    # A composer.json and composer.lock file exists, but vendor does not.
    # This means composer.json and composer.lock are copied from another location, and we now have to initialize the vendor directory.
    # We need our installer plugin first, or dependency solving will encounter missing packages that are present in the parent.

    temp_composer_json=$(tempfile -d plugins -p composer -s .json)
    temp_composer_lock=$(tempfile -d plugins -p composer -s .lock)

    cat plugins/composer.json > $temp_composer_json
    cat plugins/composer.lock > $temp_composer_lock

    init_composer_json
    init_composer_lock

    composer_install

    mv $temp_composer_json plugins/composer.json
    mv $temp_composer_lock plugins/composer.lock
fi;

if [[ ! -f "plugins/composer.json" ]]; then
    init_composer_json
    composer_install
fi;

composer --working-dir=plugins "$@"
