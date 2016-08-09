trap '[[ -e "maintenance" ]] && printf "\x1b[30;43mMaintenance mode is still enabled\x1b[0m\n" 1>&2' EXIT
if [[ ! -e "maintenance" ]]; then
    cp _maintenance.html maintenance && echo "Maintenance mode enabled"
fi;

disable_maintenance() {
    if [[ -e "maintenance.lock" ]]; then
        echo "Warning: maintenance mode is locked. Not disabling maintenance mode" 1>&2
    else
        rm maintenance && echo "Maintenance mode disabled";
    fi;
}
