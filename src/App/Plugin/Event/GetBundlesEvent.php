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

namespace App\Plugin\Event;


use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

class GetBundlesEvent extends KernelEvent
{
    /**
     * @var BundleInterface[]
     */
    private $bundles;

    public function __construct(Kernel $kernel, array $bundles)
    {
        parent::__construct($kernel);
        $this->bundles = $bundles;
    }

    /**
     * @return BundleInterface[]
     */
    public function getBundles()
    {
        return $this->bundles;
    }

    /**
     * @param BundleInterface[] $bundles
     * @return KernelEvent
     */
    public function setBundles($bundles)
    {
        $this->bundles = $bundles;

        return $this;
    }

    /**
     * @param BundleInterface $bundle
     * @return $this
     */
    public function addBundle(BundleInterface $bundle)
    {
        $this->bundles[] = $bundle;

        return $this;
    }
}
