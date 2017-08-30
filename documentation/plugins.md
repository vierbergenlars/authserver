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
