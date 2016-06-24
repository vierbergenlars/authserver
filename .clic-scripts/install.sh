#!/usr/bin/env bash
set -e
$CLIC application:execute configure "$CLIC_APPNAME"
$CLIC application:execute redeploy "$CLIC_APPNAME"

printf "$(tput setaf 2) Username for admin user$(tput sgr0):\n"
read -p " > " admin_user
printf "$(tput setaf 2) Desired password for admin user$(tput sgr0):\n"
read -sp " > " admin_pass

php app/console app:adduser --super-admin "$admin_user" "$admin_pass"
printf "Created new super-admin user $(tput setaf 3)$admin_user$(tput sgr0).\n"
