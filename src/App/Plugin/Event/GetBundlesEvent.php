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

    /**
     * @var array
     */
    private $nameMap;

    public function __construct(Kernel $kernel, array $bundles)
    {
        parent::__construct($kernel);
        $this->bundles = $bundles;
    }

    private function updateNameMap($bundles)
    {
        foreach($bundles as $bundle) {
            $this->nameMap[$bundle->getName()] = $bundle;
        }
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
        $this->nameMap = [];
        $this->updateNameMap($bundles);
        $this->bundles = $bundles;

        return $this;
    }

    /**
     * @param BundleInterface $bundle
     * @return $this
     */
    public function addBundle(BundleInterface $bundle)
    {
        if(!$this->nameMap)
            $this->updateNameMap($this->bundles);
        if(!isset($this->nameMap[$bundle->getName()])) {
            $this->updateNameMap([$bundle]);
            $this->bundles[] = $bundle;
        } else {
            $existingBundle = $this->nameMap[$bundle->getName()];
            /* @var $existingBundle \Symfony\Component\HttpKernel\Bundle\BundleInterface */
            if($existingBundle->getNamespace() !== $bundle->getNamespace())
                throw new \LogicException(sprintf('Trying to register two bundles with the same name "%s"', $existingBundle->getName()));
        }

        return $this;
    }
}
