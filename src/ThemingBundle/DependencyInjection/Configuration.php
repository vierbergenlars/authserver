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

namespace ThemingBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('theming');

        $rootNode
            ->children()
                ->scalarNode('admin_email')->defaultNull()->info('Administrator email address')->end()
                ->arrayNode('brand')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('title')->defaultValue('AuthServer')->info('Title used for the application')->end()
                        ->scalarNode('logo')->defaultNull()->info('Logo used for the application')->end()
                        ->enumNode('prefer')->values(['title', 'logo', 'both'])->defaultNull()->info('Whether to show title, logo or both in contexts where both are appropriate.')->end()
                    ->end()
                ->end()
                ->arrayNode('navbar')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('background')->defaultNull()->info('Background color for the navbar')->end()
                        ->booleanNode('inverse')->defaultFalse()->info('Enable for dark background colors')->end()
                        ->scalarNode('text_color')->defaultNull()->info('Navbar text color')->end()
                        ->scalarNode('link_color')->defaultNull()->info('Navbar link color')->end()
                        ->scalarNode('link_hover_color')->defaultNull()->info('Navbar link hover color')->end()
                    ->end()
                ->end()
        ;

        return $treeBuilder;
    }
}
