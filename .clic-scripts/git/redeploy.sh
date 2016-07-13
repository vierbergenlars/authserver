#!/usr/bin/env bash
set -e
export SYMFONY_ENV=$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)
bash deploy.sh --no-pull --commit HEAD </dev/tty >/dev/tty 2>/dev/tty
