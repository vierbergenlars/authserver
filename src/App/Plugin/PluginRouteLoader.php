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

namespace App\Plugin;


use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

class PluginRouteLoader extends Loader
{
    private $bundles;

    public function __construct(PluginManager $pluginManager)
    {
        $this->bundles = $pluginManager->getBundles();
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type or null if unknown
     *
     * @throws \Exception If something went wrong
     */
    public function load($resource, $type = null)
    {
        $routeLocation = [
            '/Resources/config/routing.yml',
            '/Resources/config/routing.xml',
            '/Resources/config/routing.php',
        ];

        $collection = new RouteCollection();

        foreach($this->bundles as $bundle)
        {
            foreach($routeLocation as $location) {
                if(file_exists($bundle->getPath().$location)) {
                    $collection->addCollection($this->import($bundle->getPath().$location));
                }
            }
        }
        return $collection;
    }

    /**
     * Returns whether this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type or null if unknown
     *
     * @return bool True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return $type === 'plugin';
    }
}
