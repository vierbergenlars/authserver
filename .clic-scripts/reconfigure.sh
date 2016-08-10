#!/usr/bin/env bash
set -e # Quit script on error

$CLIC application:variable:set "$CLIC_APPNAME" mysql/database --description="Name of the database" --default-existing-value
$CLIC application:variable:set "$CLIC_APPNAME" mysql/host --description="Hostname of the database" --if-not-global-exists --default-existing-value
$CLIC application:variable:set "$CLIC_APPNAME" mysql/user --description="Username to connect to the database" --if-not-global-exists --default-existing-value
$CLIC application:variable:set "$CLIC_APPNAME" mysql/password --description="Password of the database user"  --if-not-global-exists --default-existing-value

app_env=""
while [[ "$app_env" != "prod" && "$app_env" != "dev" ]]; do
    $CLIC application:variable:set "$CLIC_APPNAME" app/environment --description="Environment [prod|dev]" --default-existing-value --default=prod
    app_env="$($CLIC application:variable:get "$CLIC_APPNAME" app/environment)"
done;

mail_transport=""
while [[ "$mail_transport" != "mail" && "$mail_transport" != "smtp" && "$mail_transport" != "sendmail" && "$mail_transport" != "gmail" ]]; do
    $CLIC application:variable:set "$CLIC_APPNAME" mail/transport --description="Type of mail transport [mail|smtp|sendmail|gmail]" --if-not-global-exists --default-existing-value --default=mail
    mail_transport="$($CLIC application:variable:get "$CLIC_APPNAME" mail/transport)"
done;
if [[ "$mail_transport" != "mail" ]]; then
    $CLIC application:variable:set "$CLIC_APPNAME" mail/host --description="Hostname of the mail handler" --if-not-global-exists --default-existing-value
    $CLIC application:variable:set "$CLIC_APPNAME" mail/user --description="Username to connect to the mailhandler" --if-not-global-exists --default-existing-value
    $CLIC application:variable:set "$CLIC_APPNAME" mail/password --description="Password of the mail user" --if-not-global-exists --default-existing-value
    $CLIC application:variable:set "$CLIC_APPNAME" mail/encryption --description="Encryption type for mail [ssl|tls]" --if-not-global-exists --default-existing-value
fi;

$CLIC application:variable:set "$CLIC_APPNAME" mail/sender --description="Sender address of mails"  --default-existing-value
$CLIC application:variable:set "$CLIC_APPNAME" app/configured 1

exec $CLIC application:execute "$CLIC_APPNAME" configure