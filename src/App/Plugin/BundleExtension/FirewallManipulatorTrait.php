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

/**
 * Makes it easier for bundles to modify the firewall configuration for the {@link \App\Plugin\PluginEvents::CONTAINER_CONFIG} event.
 */
trait FirewallManipulatorTrait
{
    /**
     * Get the complete firewall configuration
     * @param ContainerConfigEvent $event
     * @return array
     */
    private function getFirewallConfig(ContainerConfigEvent $event)
    {
        $config = $event->getConfig();
        $pa = PropertyAccess::createPropertyAccessor();
        $firewall = $pa->getValue($config, '[security][firewalls]');

        return $firewall?:[];
    }

    /**
     * Set the complete firewall configuration
     * @param ContainerConfigEvent $event
     * @param array $firewall
     */
    private function setFirewallConfig(ContainerConfigEvent $event, $firewall)
    {
        $pa = PropertyAccess::createPropertyAccessor();
        $config = $event->getConfig();
        $pa->setValue($config, '[security][firewalls]', $firewall);
        $event->setConfig($config);
    }

    /**
     * Adds new firewall(s)
     *
     * @param ContainerConfigEvent $event
     * @param array $newFw Associative array of new firewalls to add.
     * @param bool|string $before If true, the firewall is added as first entry.
     *                            If false, the firewall is added as last entry.
     *                            If a string, the firewall is added before the firewall with the string as name
     */
    private function addFirewall(ContainerConfigEvent $event, $newFw, $before = false)
    {
        $firewall = $this->getFirewallConfig($event);

        $newFirewall = [];

        if($before === true) {
            $before = key($firewall);
        }
        if($before === false || !isset($firewall[$before])) {
            $newFirewall = $firewall;
            foreach($newFw as $k => $v)
                $newFirewall[$k] = $v;
        } else {
            foreach($firewall as $key => $config) {
                if($before === $key) {
                    foreach($newFw as $k => $v)
                        $newFirewall[$k] = $v;
                }
                $newFirewall[$key] = $config;
            }
        }

        $this->setFirewallConfig($event, $newFirewall);

    }

}
