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


final class PluginEvents
{
    /**
     * Emitted before the list of enabled bundles is finalized.
     *
     * Use this event to customize the list of enabled bundles
     * Emits {@link Event\GetBundlesEvent}
     */
    const INITIALIZE_BUNDLES = 'app.plugin.initialize_bundles';

    /**
     * Emitted after the container has been initialized.
     *
     * Emits {@link Event\KernelEvent}
     */
    const INITIALIZE_CONTAINER = 'app.plugin.initialize_container';

    /**
     * Emitted when the container loads its configuration
     *
     * Use this event to inject extra configuration that needs to be available
     * before the {@link Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface::prepend()}
     * method is called.
     * Emits {@link Event\ContainerConfigEvent}
     */
    const CONTAINER_CONFIG = 'app.plugin.container_config';

    private function __construct()
    {
    }

}
