<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Admin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MimeTypeListener implements EventSubscriberInterface
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        switch ($event->getRequest()->getRequestFormat()) {
            case 'xml':
            case 'json':
                $matches = [];
                if (preg_match('/^admin_(?P<object>[a-z_]+)_(?P<type>[a-z]+)/', $event->getRequest()->attributes->get('_route'), $matches)) {
                    $headers = $event->getResponse()->headers;
                    if ($headers->get('content-type') !== null) {
                        list($mainType,$subType) = explode('/', $headers->get('content-type'), 2);
                        $headers->set('content-type', $mainType.'/vnd.be.vbgn.authserver.api.'.$matches['object'].($matches['type']=='gets'?'.list':'').'+'.$subType);
                    }
                }

                break;
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

}
