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

namespace Admin\EventListener;

use App\AppEvents;
use App\Event\MenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class AdminMenuListener implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getSubscribedEvents()
    {
        return [AppEvents::MAIN_MENU => 'onMainMenu'];
    }

    public function onMainMenu(MenuEvent $event)
    {
        try {
            if($this->authorizationChecker->isGranted('ROLE_SCOPE_R_PROFILE'))
                $event->addChild('users', [
                    'route' => 'admin_user_gets',
                    'label' => '.icon-user Users'
                ]);

            if($this->authorizationChecker->isGranted('ROLE_SCOPE_R_GROUP'))
                $event->addChild('groups', [
                    'route' => 'admin_group_gets',
                    'label' => '.icon-group Groups',
                ]);

            if($this->authorizationChecker->isGranted('ROLE_AUDIT'))
                $event->addChild('audit', [
                    'route' => 'admin_audit_gets',
                    'label' => '.icon-heartbeat Audit Log'
                ]);

            if($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
                $moreMenu = $event->addChild('admin_more', [
                    'label' => '.icon-ellipsis-v More'
                ]);

                $moreMenu->addChild('oauth_client', [
                    'route' => 'admin_oauth_client_gets',
                    'label' => '.icon-cube OAuth Apps'
                ]);

                $moreMenu->addChild('apikey', [
                    'route' => 'admin_apikey_gets',
                    'label' => '.icon-key API keys'
                ]);

                $moreMenu->addChild('property_namespace', [
                    'route' => 'admin_property_namespace_gets',
                    'label' => '.icon-object-group Property namespaces'
                ]);
            }

        } catch(AuthenticationCredentialsNotFoundException $ex) {
            // Thrown when there is no token (on error pages)
        }
    }

}
