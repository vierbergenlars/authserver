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

namespace App\Plugin\BundleExtension;


use App\Plugin\Event\ContainerConfigEvent;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Makes it easier for bundles to modify the configuration for the {@link \App\Plugin\PluginEvents::CONTAINER_CONFIG} event.
 */
class ConfigManipulator
{
    private $propertyAccessor;
    /**
     * @var ContainerConfigEvent
     */
    private $event;
    private $propertyPath;


    public function __construct(ContainerConfigEvent $event, $propertyPath)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->event = $event;
        $this->propertyPath = new PropertyPath($propertyPath);
    }

    /**
     * Get the complete configuration at the defined property path
     * @return array
     */
    public function getConfig()
    {
        $config = $this->event->getConfig();
        return $this->propertyAccessor->getValue($config, $this->propertyPath);
    }

    /**
     * Set the complete configuration at the defined property path
     * @param array $newConfig
     */
    public function setConfig($newConfig)
    {
        $config = $this->event->getConfig();
        $this->propertyAccessor->setValue($config, $this->propertyPath, $newConfig);
        $this->event->setConfig($config);
    }

    /**
     * Adds a piece of configuration before the specified key
     * @param array $newConfig Associative array of new configuration to add
     * @param mixed|null $before The key to prepend the new configuration before. If null, prepends to the front of the configuration.
     */
    public function prependConfig($newConfig, $before = null)
    {
        $config = $this->getConfig();
        $finalConfig = [];

        if($before === null && $config)
            $before = key($config);
        if(!isset($config[$before])) {
            $finalConfig = $config;
            foreach($newConfig as $k => $v)
                $finalConfig[$k] = $v;
        } else {
            foreach($config as $key => $conf) {
                if($before === $key) {
                    foreach($newConfig as $k => $v)
                        $finalConfig[$k] = $v;
                }
                $finalConfig[$key] = $conf;
            }
        }

        $this->setConfig($finalConfig);
    }

    /**
     * Adds a piece of configuration after the specified key
     * @param array $newConfig Associative array of new configuration to add
     * @param mixed|null $after The key to append the new configuration after. If null, appends to the end of the configuration.
     */
    public function appendConfig($newConfig, $after = null)
    {
        $config = $this->getConfig();
        $finalConfig = [];

        if($after === null || !isset($config[$after])) {
            $finalConfig = $config;
            foreach($newConfig as $k => $v)
                $finalConfig[$k] = $v;
        } else {
            foreach($config as $key => $conf) {
                $finalConfig[$key] = $conf;
                if($after === $key) {
                    foreach($newConfig as $k => $v)
                        $finalConfig[$k] = $v;
                }
            }
        }

        $this->setConfig($finalConfig);
    }
}
