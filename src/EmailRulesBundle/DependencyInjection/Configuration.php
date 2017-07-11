<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace EmailRulesBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('email_rules');

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->arrayNode('rules')
                    ->cannotBeOverwritten()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('regex_match')->defaultNull()->info('Email address must match this regex')->end()
                            ->scalarNode('domain')->defaultNull()->info('Email address must be in this domain name')->end()
                            ->arrayNode('groups')
                                ->prototype('scalar')->end()
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) { return [$v]; })
                                ->end()
                                ->info('Groups the user will be added on validation of email address')
                            ->end()
                            ->enumNode('role')
                                ->defaultValue('ROLE_USER')
                                ->values(array('ROLE_USER', 'ROLE_AUDIT', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'))
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) { return strtoupper($v); })
                                ->end()
                                ->info('Role the user will be given on validation of email address')
                            ->end()
                            ->booleanNode('reject')->defaultFalse()->info('Rejects the email address')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
