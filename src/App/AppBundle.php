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

namespace App;

use App\Plugin\Event\ContainerConfigEvent;
use App\Plugin\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::CONTAINER_CONFIG => 'loadFirewallConfig',
        ];
    }

    public function loadFirewallConfig(ContainerConfigEvent $event)
    {
        $configManipulator = $event->getConfigManipulator('[security][firewalls]');
        if($event->getKernel()->getEnvironment() === 'dev') {
            $configManipulator->prependConfig([
                'dev' => [
                    'pattern'  => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ]
            ]);
        }


        $configManipulator->appendConfig([
            'oauth_token' => [
                'pattern' => '^/oauth/v2/token',
                'security' => false
            ],
            'api' => [
                'pattern' => '^/api',
                'http_basic' => null,
                'fos_oauth' => true,
                'stateless' => true,
            ],
            'public' => [
                'pattern' => '^/',
                'form_login' => [
                    'login_path' => 'app_login',
                    'check_path' => 'app_login_check',
                ],
                'simple_preauth' => [
                    'authenticator' => 'app.admin.security.apikey_authenticator',
                ],
                'logout' => [
                    'handlers' => ['app.security.logout_handler'],
                ],
                'anonymous' => null,
                'switch_user' => true
            ]
        ]);
    }
}
