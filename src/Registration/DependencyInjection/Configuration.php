<?php

namespace Registration\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('registration');

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->arrayNode('email_rules')
                    ->cannotBeOverwritten()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('regex_match')->defaultNull()->info('Email address must match this regex')->end()
                            ->scalarNode('domain')->defaultNull()->info('Email address must be in this domain name')->end()
                            ->arrayNode('default_groups')
                                ->prototype('scalar')->end()
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) { return [$v]; })
                                ->end()
                                ->info('Groups the user will be added on registration')
                            ->end()
                            ->booleanNode('self_registration')->defaultFalse()->info('If registration with an email address matching these rules should be allowed')->end()
                            ->booleanNode('auto_activate')->defaultFalse()->info('If the registered user is activated automatically after registration')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('registration_message')->defaultNull()->info('Message that is shown above the registration form')->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
