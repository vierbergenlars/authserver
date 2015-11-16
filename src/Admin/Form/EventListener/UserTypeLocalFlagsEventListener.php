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

namespace Admin\Form\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserTypeLocalFlagsEventListener implements EventSubscriberInterface
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!$this->authorizationChecker->isGranted('ROLE_SCOPE_W_PROFILE_ADMIN')) {
            $form->remove('role');
        }
        if($form->getConfig()->getMethod() !== 'POST') {
            if (!$this->authorizationChecker->isGranted('ROLE_SCOPE_W_PROFILE_CRED')) {
                $form->remove('password');
                $form->remove('passwordEnabled');
            }
            if (!$this->authorizationChecker->isGranted('ROLE_SCOPE_W_PROFILE_EMAIL')) {
                $form->remove('emailAddresses');
            }
            if (!$this->authorizationChecker->isGranted('ROLE_SCOPE_W_PROFILE_GROUPS')) {
                $form->remove('groups');
            }
            if (!$this->authorizationChecker->isGranted('ROLE_SCOPE_W_PROFILE_USERNAME')) {
                $form->remove('username');
            }
            if (!$this->authorizationChecker->isGranted('ROLE_SCOPE_W_PROFILE_ENABLED')) {
                $form->remove('enabled');
            }
        }
        if ($this->authorizationChecker->isGranted('ROLE_API')) {
            // Remove user properties fields when logged in with API key
            $form->remove('groups');
        }
        if ($data instanceof User) {
            /* @var $data \App\Entity\User */
            if ($data->getRole() == 'ROLE_SUPER_ADMIN' &&!$this->authorizationChecker->isGranted('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')) {
                $form->remove('enabled');
            }
        }
    }
}
