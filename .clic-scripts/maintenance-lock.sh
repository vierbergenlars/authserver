#!/usr/bin/env bash
set -e # Quit the script on error
touch maintenance.lock
cp _maintenance.html maintenance
echo "Maintenance mode locked"
