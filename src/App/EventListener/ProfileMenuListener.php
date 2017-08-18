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

namespace App\EventListener;


use App\AppEvents;
use App\Entity\User;
use App\Event\MenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileMenuListener implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [AppEvents::PROFILE_MENU => 'onProfileMenu'];
    }

    public function onProfileMenu(MenuEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?$token->getUser():null;

        if(!$user instanceof User) {
            $event->addChild('login', [
                'route' => 'app_login',
                'label' => '.icon-sign-in Sign in',
            ]);
        } else {
            $userMenu = $event->addChild('user', [
                'label' => '.icon-user '.$user->getDisplayName(),
            ]);

            $userMenu->addChild('profile', [
                'route' => 'user_profile',
                'label' => '.icon-user Profile',
            ]);

            $userMenu->addChild('divider_1', [
                'attributes' => [
                    'divider' => true,
                ]
            ]);

            $userMenu->addChild('logout', [
                'route' => 'logout',
                'label' => '.icon-sign-out Sign out',
            ]);
        }

    }
}
