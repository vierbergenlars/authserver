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


use App\Plugin\BundleExtension\ConfigManipulator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class ContainerConfigEvent extends KernelEvent
{
    private $containerBuilder;
    private $config;

    /**
     * ContainerConfigEvent constructor.
     */
    public function __construct(Kernel $kernel, ContainerBuilder $containerBuilder)
    {
        parent::__construct($kernel);

        $this->containerBuilder = $containerBuilder;
        $this->config = [];
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainerBuilder()
    {
        return $this->containerBuilder;
    }


    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     * @return ContainerConfigEvent
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param string $propertyPath
     * @return ConfigManipulator
     */
    public function getConfigManipulator($propertyPath)
    {
        return new ConfigManipulator($this, $propertyPath);
    }
}
