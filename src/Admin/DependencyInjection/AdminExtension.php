<?php

namespace Admin\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AdminExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $servicesDirectory = __DIR__.'/../Resources/config/services';
        $fileLocator = new FileLocator($servicesDirectory);
        $loader = new Loader\XmlFileLoader($container, $fileLocator);

        $configFiles = $finder = Finder::create()
            ->in($servicesDirectory)
            ->files();

        foreach($configFiles as $configFile) {
            $loader->load($configFile->getRelativePathName());
        }
    }
}
