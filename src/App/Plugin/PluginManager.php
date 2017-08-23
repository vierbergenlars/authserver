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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;


class PluginManager
{
    /**
     * @var PluginBundleFetcher
     */
    private $fetcher;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Cache of loaded bundles
     * @var BundleInterface[]|null
     */
    private $bundles;

    public function __construct(PluginBundleFetcher $fetcher, EventDispatcherInterface $eventDispatcher)
    {
        $this->fetcher = $fetcher;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return \Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function getBundles()
    {
        if(!$this->bundles)
            $this->bundles = $this->fetcher->getBundles();
        return $this->bundles;
    }

}
