# Nginx auth_request

The Nginx `auth_request` functionality uses an internal request to determine if a user is authorized to access a resource.

Support for this functionality is enabled by enabling the `auth_request` bundle in `app/config/parameters.yml`:

```yaml
auth_request:
    enabled: true
```

Nginx configuration:

```nginx
server {
    # ...
    auth_request /auth;

    location = /auth {
        auth_request off;
        proxy_pass https://auth.vbgn.be/api/auth_request/basic?groups[]=admin;
        proxy_pass_request_body off;
        proxy_set_header Content-Length "";
        proxy_set_header Authorization $http_authorization;
        internal;

        # Recommended settings when the authentication server is using https
        proxy_ssl_server_name on;
        proxy_ssl_verify on;
        proxy_ssl_verify_depth 3;
        proxy_ssl_trusted_certificate /etc/ssl/certs/ca-certificates.crt;
    }
}
```

## Basic authentication

Authentication based on HTTP Basic Authentication is provided by the `/api/auth_request/basic` endpoint.

By default, all active users are considered authorized. Authorized users can be limited by group and/or evaluation of an expression.

Limiting authorized users by group is handled by the `groups[]` query parameter.
The user is required to be a member of all requested groups.

For example, the endpoint `/api/auth_request/basic?groups[]=kd_admins` limits authorized users to the ones that are member of the `kd_admins` group.

> Note: This functionality only works for exported groups. Non-exported groups will always result in authorization failure.

> Best practice: Although the endpoint supports passing multiple groups,
> it is preferred to construct the group hierarchy so one or more application-specific groups contains all users
> with a certain authorization level inside the application.

Authorized users can be limited with an expression by using the `eval` query parameter.
The expression is very flexible and has full access to the user object to determine authorization.

For example, the expression `user.getUsername() == "vierbergenlars" or has_group("%sysops")` (endpoint URL `/api/auth_request/basic?eval=user.username+%3D%3D+%22vierbergenlars%22+or+has_group%28%22%25sysops%22%29`)
allows the user with username `vierbergenlars` and all users that are member of the `%sysops` group.

> Note: The `has_group()` function only works for exported groups.

> Warning: Any expression has to be properly URL encoded.
