# Email rules

Users can be automatically placed in groups based on their verified email addresses by enabling the email rules module in `app/config/parameters.yml`.

```yaml
email_rules:
    enabled: true
    rules:
      - { domain: vbgn.be, groups: ['%sysops'], role: ROLE_SUPER_ADMIN }
      - { regex_match: '/example\.com$/', groups: ['example'] }
```

This configuration adds all `@vbgn.be` addresses to the `%sysops` group and grants them the `ROLE_SUPER_ADMIN` role.

When an email address is verified, the user is added to the groups and gets their role upgraded automatically as
determined by the rules. These rules are also applied when users are modified from the admin interface.

Matching is performed from top to bottom, and stops at the first matching expression.

## Rejecting an email address

Besides adding users to groups or granting roles, it is possible to refuse certain email addresses.

This feature can be used to prevent users from adding blacklisted email addresses to their account.

```yaml
email_rules:
  enabled: true
  rules:
    - { domain: example.com, reject: true }
```

It can also be used to reject all email addresses that do not match a whitelist by adding a reject without a domain as final rule.

```yaml
email_rules:
  enabled: true
  rules:
    - { domain: vbgn.be }
    - { reject: true }
```

