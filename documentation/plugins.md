# Plugins

Bundles placed in the `plugins/` folder are automatically loaded by the application.

## Managing installed plugins

Plugins can be installed and removed with the `plugin.sh` script, which is a wrapper for [the `composer` package manager](https://getcomposer.org/).

Installing a plugin is as simple as running `./plugin.sh require $PLUGIN_NAME`,
with `$PLUGIN_NAME` the name of the plugin on [Packagist](https://packagist.org/search/?type=authserver-plugin).

An installed plugin typically has to be configured, which is done in the `app/config/parameters.yml` file.

Removing an installed plugin is accomplished by running `./plugin.sh remove $PLUGIN_NAME`.

A plugin can be updated by running `./plugin.sh update $PLUGIN_NAME`,
or all plugins can be updated with `./plugin.sh update`.

## Developing plugins

A plugin is a single [Symfony bundle](http://symfony.com/doc/current/bundles.html),
all files ending in `Bundle.php` are loaded and registered as bundles.
To prevent traversal of the whole dependency graph of a bundle,
the bundle locator does not descend into `vendor` directories.

File paths in the following description are relative to the location of the bundle file.

While autoloading of composer-installed plugins happens automatically, manually installed plugins (by extracting a zip file to the plugins folder)
require an `autoload.php` and/or `vendor/autoload.php` file in which autoloading is set up for the bundle.

Routing for a bundle is defined in `Resources/config/routing.yml`, `Resources/config/routing.xml` or `Resources/config/routing.php`.
These files are processed by the standard Symfony route loaders for their respective type.

Doctrine database migrations for a bundle are located in `Resources/migrations`.
These are loaded automatically when migrations are run for the application.
