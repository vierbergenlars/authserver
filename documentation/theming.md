# Theming

Authserver can be themed to match branding colors and naming.

The theming module can be enabled in `app/config/parameters.yml`.

All configuration is optional. Default values are used for configuration parameters that are not present.

## Brand

The application title in the navbar and title can be changed to something more appropriate than "Authserver".

When a logo is specified, it can be specified whether is should appear instead of the application title (default), or next to the application title.

```yaml
theming:
    brand:
      title: ACME login
      logo: https://example.com/logo.png
      prefer: both # Or title or logo
```

## Admin email

Shows the support email address for this application in the footer.
No email address will be shown if this parameter is not present.

```yaml
theming:
    admin_email: support@example.com
```

## Navbar

The background color of the navbar on top of the page is configurable to match branding.
Set `inverse` to true to use a light on dark color scheme.

```yaml
theming:
    navbar:
      background: rebeccapurple
      inverse: true
```

The default text color might not always look good on the chosen background color.
Text color, link text color and link hover text color can be changed from their defaults.

Link text color defaults to the same as text color when it is not specified.

```yaml
theming:
    navbar:
      background: rebeccapurple
      inverse: true
      text_color: darken(white, 10%)
      hover_link_color: white
```



