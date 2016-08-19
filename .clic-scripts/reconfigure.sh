#!/usr/bin/env bash
set -e # Quit script on error

if [[ ! tty -s ]]; then
    printf "$(tput setab 1)You are not on a terminal, so we cannot ask you configuration questions.$(tput sgr0)\n"
    printf "Run $(tput setaf 2)$CLIC application:execute \"$CLIC_APPNAME\" reconfigure$(tput sgr0) in a terminal to configure interactively"
    exit 1
fi

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

user_registration=""
while [[ "$user_registration" != "y" && "$user_registration" != "n" ]]; do
    $CLIC application:variable:set "$CLIC_APPNAME" app/registration --description="Allow users to register an account? [y|n]" --default-existing-value
    user_registration="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration)"
done;

add_another_domain="$user_registration"

i=1
while [[ "$add_another_domain" == "y" ]]; do
    $CLIC application:variable:set "$CLIC_APPNAME" app/registration/$i/domain --description="Rule $i: To which domain should the email addresses belong to match this rule? (Use '*' for any)" --default-existing-value --default="*"
    $CLIC application:variable:set "$CLIC_APPNAME" app/registration/$i/regex_match --description="Rule $i: Which regex should this email address match to match this rule (including delimiters)? (Use '*' for any)" --default-existing-value --default="*"
    self_registration=""
    while [[ "$self_registration" != "y" && "$self_registration" != "n" ]]; do
        $CLIC application:variable:set "$CLIC_APPNAME" app/registration/$i/self_registration --description="Rule $i: Allow self-registration for email addresses matching this rule? [y|n]" --default-existing-value --default=y
        self_registration="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/self_registration)"
    done
    auto_activate=""
    while [[ "$auto_activate" != "y" && "$auto_activate" != "n" ]]; do
        $CLIC application:variable:set "$CLIC_APPNAME" app/registration/$i/auto_activate --description="Rule $i: Auto-activate users with email addresses matching this rule? [y|n]" --default-existing-value --default=n
        auto_activate="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/auto_activate)"
    done

    $CLIC application:variable:set "$CLIC_APPNAME" app/registration/$i/groups --description="Rule $i: Automatically put users with email addresses matching this rule in following groups (comma separated, '.' to leave empty)" --default-existing-value --default="."

    role=""
    while [[ "$role" != "ROLE_USER" && "$role" != "ROLE_AUDIT" && "$role" != "ROLE_ADMIN" && "$role" != "ROLE_SUPER_ADMIN" ]]; do
        $CLIC application:variable:set "$CLIC_APPNAME" app/registration/$i/role --description="Rule $i: Automatically assign this role to users with email addresses matching this rule. [ROLE_USER|ROLE_AUDIT|ROLE_ADMIN|ROLE_SUPER_ADMIN]" --default-existing-value --default=ROLE_USER
        role="$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/$i/role)"
    done

    add_another_domain=""
    if [[ "$i" -gt "$($CLIC application:variable:get "$CLIC_APPNAME" app/registration/count || echo 0)" ]]; then
        default_action="n"
    else
        default_action="y"
    fi
    while [[ "$add_another_domain" != "y" && "$add_another_domain" != "n" ]]; do
        printf "$(tput setaf 2) Add another email rule? [y|n]$(tput sgr0) [$(tput setaf 3)$default_action$(tput sgr0)]:\n"
        read -p " > " add_another_domain
        if [[ "$add_another_domain" == "" ]]; then
            add_another_domain="$default_action"
        fi
    done;
    i=$(($i+1))
    $CLIC application:variable:set "$CLIC_APPNAME" app/registration/count "$i"
done;

if [[ "$user_registration" == "y" ]]; then
    $CLIC application:variable:set "$CLIC_APPNAME" app/registration/message --description="Message to show to users on the registration page. (Use '#' for no message)" --default-existing-value --default="#"
fi

$CLIC application:variable:set "$CLIC_APPNAME" app/configured 1

exec $CLIC application:execute "$CLIC_APPNAME" configure
