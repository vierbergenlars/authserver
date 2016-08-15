<?php

namespace Registration\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class RegistrationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if($config['enabled']) {
            $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
            $loader->load('services.xml');

            $emailRules = array_map(function($emailRule) {
                $rule = new DefinitionDecorator('registration.rule.abstract');
                $rule->replaceArgument(0, $emailRule['regex_match']);
                $rule->replaceArgument(1, $emailRule['domain']);
                $rule->replaceArgument(2, $emailRule['default_groups']);
                $rule->replaceArgument(3, $emailRule['self_registration']);
                $rule->replaceArgument(4, $emailRule['auto_activate']);
                $rule->setAbstract(false);
                return $rule;
            }, $config['email_rules']);

            $container->getDefinition('registration.rules')
                ->replaceArgument(0, $emailRules);

            $container->setParameter('registration.message', $config['registration_message']);
        }

    }
}
