#!/usr/bin/env bash
set -e
$CLIC application:execute "$CLIC_APPNAME" configure

if tty -s; then
    printf "$(tput setaf 2) Username for admin user$(tput sgr0):\n"
    read -p " > " admin_user
    printf "$(tput setaf 2) Desired password for admin user$(tput sgr0):\n"
    read -sp " > " admin_pass

    php app/console app:adduser --super-admin "$admin_user" "$admin_pass"
    printf "Created new super-admin user $(tput setaf 3)$admin_user$(tput sgr0).\n"
else
    printf "$(tput setab 1)You are not on a terminal, so no super-admin user could be created.$(tput sgr0)\n"
    printf "Run $(tput setaf 2)$CLIC application:execute \"$CLIC_APPNAME\" install$(tput sgr0) in a terminal to create a user"
fi
