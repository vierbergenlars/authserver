#!/usr/bin/env bash
set -e # Quit the script on error
rm -f maintenance.lock maintenance
echo "Maintenance mode unlocked"
