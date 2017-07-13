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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class EmailRulesExtension extends Extension
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
                $rule = new DefinitionDecorator('email_rules.rule.abstract');
                $rule->replaceArgument(0, $emailRule['regex_match']);
                $rule->replaceArgument(1, $emailRule['domain']);
                $rule->replaceArgument(2, $emailRule['role']);
                $rule->replaceArgument(3, $emailRule['groups']);
                $rule->replaceArgument(4, $emailRule['reject']);
                $rule->setAbstract(false);
                return $rule;
            }, $config['rules']);

            $container->getDefinition('email_rules.rules')
                ->replaceArgument(0, $emailRules);
        }
    }
}
