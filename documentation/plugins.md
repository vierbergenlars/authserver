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

### Structure

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

### Plugin events

During the booting of the AppKernel, events are emitted to let plugins hook into the boot phase.
As the container is not yet available during the boot phase, these events are handled by a separate
event dispatcher. To subscribe to these events, let your Bundle class implement the `Symfony\Component\EventDispatcher\EventSubscriberInterface` interface.

 * `App\Plugin\PluginEvents::INITIALIZE_BUNDLES`: Emitted after plugin bundles are located,
 but before bundles are registered in the kernel. This event can be used to modify the list of bundles
 to be loaded (for example, when a plugin is dependent on another bundle). Emits a `App\Plugin\Event\GetBundlesEvent`.
 * `App\Plugin\PluginEvents::INITIALIZE_CONTAINER`: Emitted after the container has been initialized.
  I don't know why it would be useful, as everything is already initialized at this point. Emits a `App\Plugin\Event\KernelEvent`.
 * `App\Plugin\PluginEvents::CONTAINER_CONFIG`: Emitted when the container loads its configuration.
  Use this event to inject extra configuration that needs to be available
  before the `Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface::prepend()`
  method is called. It can also be used to let multiple bundles modify configurations that are dependent on order,
  and disallow adding new array keys in multiple configuration files (like `security.firewalls`).
