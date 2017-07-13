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
                            ->scalarNode('regex_match')
                                ->defaultNull()
                                ->validate()
                                    ->ifTrue(function($value) {
                                        return @preg_match($value, '') === false;
                                    })
                                    ->thenInvalid('The regex %s is not valid.')
                                ->end()
                                ->info('Email address must match this regex')
                            ->end()
                            ->scalarNode('domain')->defaultNull()->info('Email address must be in this domain name')->end()
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
