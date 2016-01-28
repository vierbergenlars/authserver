# Security

For a properly secured application, a couple of minor changes must be made to the application.

> You MUST change `parameters.secret` in `app/config/parameters.yml`

To prevent unauthorized access to private files, you MUST NOT expose any directories other than `web/` to the public.

> You MUST sett the `DocumentRoot` to the `web/` directory.

Unless you are running the site in development, you SHOULD force the use https for all requests to this application.

> Add to `web/.htaccess`, after `RewriteEngine On`:
> ```
# Redirect to HTTPS on all URLS
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

To prevent cookie leakage on the initial request over http, before the redirect to https, cookies MUST be marked secure. 

> Append to `app/config/parameters.yml`:
> ```yaml
framework:
    session:
        cookie_secure: true
```
