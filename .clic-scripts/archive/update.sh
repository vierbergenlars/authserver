#!/usr/bin/env bash
set -e
export SYMFONY_ENV=$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)
mkdir -p .clic-scripts/tmp
bash deploy.sh -l
current_archive=$($CLIC config:get "applications[$CLIC_APPNAME][archive-url]")
if [[ ${current_archive} =~ .*/master\.(tar\.gz|zip) || ${current_archive} =~ .*/(tar|zip)ball/master ]]; then
    tarball_url="$current_archive"
else
    if [[ ${current_archive} =~ https://api\.github\.com/repos/.* ]]; then
        # API style URL
        repo=$(echo "$current_archive" | awk 'BEGIN { FS="/"}; {print $5"/"$6}')
    elif [[ ${current_archive} =~ https://(www\.)?github\.com/.* ]]; then
        # Github web style URL
        repo=$(echo "$current_archive" | awk 'BEGIN { FS="/"}; {print $4"/"$5}')
    else
        echo "Cannot determine archive type; you'll have to download and extract a new version yourself." >&2
        exit 1
    fi
    tarball_url=$(curl https://api.github.com/repos/${repo}/tags | php -r 'echo json_decode(file_get_contents("php://stdin"), true)[0]["tarball_url"];')
    if [[ "$current_archive" == "$tarball_url" ]]; then
        echo "No update available"
        exit 0
    fi
fi
mv .clic-scripts/tmp/update.tar.gz .clic-scripts/tmp/update-prev.tar.gz
wget "$tarball_url" -O .clic-scripts/tmp/update.tar.gz
tar xf .clic-scripts/tmp/update.tar.gz -C . --strip-components=1
removed_files=$(diff <(tar tf .clic-scripts/tmp/update-prev.tar.gz | sed 's/^[^\/]*\///' | sort) <(tar tf .clic-scripts/tmp/update.tar.gz | sed 's/^[^\/]*\///' | sort) | grep '^< ' | sed 's/^< //' | grep -v '/$')
for removed_file in ${removed_files}; do
    rm $(pwd)/${removed_file};
done
$CLIC application:exec "$CLIC_APPNAME" redeploy
$CLIC config:set "applications[$CLIC_APPNAME][archive-url]" "$tarball_url"
