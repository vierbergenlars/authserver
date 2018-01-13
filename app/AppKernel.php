<?php

use App\Plugin\Event\ContainerConfigEvent;
use App\Plugin\Event\GetBundlesEvent;
use App\Plugin\Event\KernelEvent;
use App\Plugin\PluginBundleFetcher;
use App\Plugin\PluginEvents;
use App\Plugin\PluginManager;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    /**
     * @var PluginManager
     */
    private $pluginManager;

    /**
     * @var EventDispatcherInterface
     */
    private $pluginEventDispatcher;

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle(),
            new Hautelook\TemplatedUriBundle\HautelookTemplatedUriBundle(),
            new Bazinga\Bundle\RestExtraBundle\BazingaRestExtraBundle(),
            new Braincrafted\Bundle\BootstrapBundle\BraincraftedBootstrapBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new App\AppBundle(),
            new Admin\AdminBundle(),
            new User\UserBundle(),
            new Registration\RegistrationBundle(),
            new AuthRequestBundle\AuthRequestBundle(),
            new EmailRulesBundle\EmailRulesBundle(),
            new ThemingBundle\ThemingBundle(),
            new OAuth2\ServerBundle\OAuth2ServerBundle(),
            new OAuthBundle\OAuthBundle()
        );

        $this->loadPluginManager();

        // Load plugins and add them to the bundles list
        foreach($this->pluginManager->getBundles() as $bundle) {
            $bundles[] = $bundle;
        }

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        // Subscribe all bundles implementing the EventSubscriberInterface to the plugin EventDispatcher
        foreach($bundles as $bundle) {
            if($bundle instanceof EventSubscriberInterface) {
                $this->pluginEventDispatcher->addSubscriber($bundle);
            }
        }

        // Emit Initialize Bundles event to fetch the final list of bundles to be loaded.
        $event = new GetBundlesEvent($this, $bundles);
        $this->pluginEventDispatcher->dispatch(PluginEvents::INITIALIZE_BUNDLES, $event);
        return $event->getBundles();
    }

    /**
     * Load the plugin fetcher, manager and event dispatcher
     */
    private function loadPluginManager()
    {
        $pluginCache = $this->getCacheDir()?new ConfigCache($this->getCacheDir().'/plugins.php', $this->isDebug()):null;
        $pluginFetcher = new PluginBundleFetcher($this->getProjectDir() . '/plugins', $pluginCache);
        $this->pluginEventDispatcher = new EventDispatcher();
        $this->pluginManager = new PluginManager($pluginFetcher, $this->pluginEventDispatcher);
    }

    protected function initializeContainer()
    {
        parent::initializeContainer();

        // Add plugin manager and plugin EventDispatcher as services
        $this->getContainer()->set('app.plugin.manager', $this->pluginManager);
        $this->getContainer()->set('app.plugin.event_dispatcher', $this->pluginEventDispatcher);

        // Emit Initialize Container event
        $event = new KernelEvent($this);
        $this->pluginEventDispatcher->dispatch(PluginEvents::INITIALIZE_CONTAINER, $event);
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // Enable event subscribers to add their own configuration before the normal configuration is loaded.
        // The primary usecase is letting multiple bundles configure security.firewall incrementally.
        $loader->load(\Closure::bind(function(ContainerBuilder $containerBuilder) {
            $event = new ContainerConfigEvent($this, $containerBuilder);
            $this->pluginEventDispatcher->dispatch(PluginEvents::CONTAINER_CONFIG, $event);
            foreach($event->getConfig() as $ns => $values) {
                $containerBuilder->loadFromExtension($ns, $values);
            }
        }, $this));

        // Load normal configuration
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
