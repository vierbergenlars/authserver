<?php
/*
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015 Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace User\Controller\Api;

use App\Entity\Group;
use App\Entity\User;
use FOS\RestBundle\Controller\Annotations\View;

class UserController extends BaseController
{

    /**
     * @View
     */
    public function getAction()
    {
        $this->denyAccessIfGrantedScope('openid');
        $user = $this->getUser();
        /* @var $user User */
        $exportableGroups = array_filter($user->getGroupsRecursive(), function (Group $group) {
            return $group->isExportable();
        });
        $groups = array_map(function (Group $group) {
            return $group->getName();
        }, $exportableGroups);

        return array(
            'guid' => $user->getGuid(),
            'username' => $this->isGrantedScope('profile:username') ? $user->getUsername() : null,
            'name' => $this->isGrantedScope('profile:realname') ? $user->getDisplayName() : null,
            'groups' => $this->isGrantedScope('profile:groups') ? $groups : array(),
            'primary-email' => $this->isGrantedScope('profile:email') && $user->getPrimaryEmailAddress() ? $user->getPrimaryEmailAddress()->getEmail() : null
        );
    }

    /**
     * Userinfo endpoint for OpenID Connect
     * @View
     */
    public function getInfoAction()
    {
        $this->denyAccessUnlessGrantedScope('openid');
        $user = $this->getUser();
        /* @var $user User */
        return array(
            'sub' => $user->getGuid(),
            'name' => $this->isGrantedScope('profile') ? $user->getDisplayName() : null,
            'preferred_username' => $this->isGrantedScope('profile') ? $user->getUsername() : null,
            'email' => $this->isGrantedScope('email') && $user->getPrimaryEmailAddress() ? $user->getPrimaryEmailAddress()->getEmail() : null,
            'email_verified' => $this->isGrantedScope('email') && $user->getPrimaryEmailAddress() ? $user->getPrimaryEmailAddress()->isVerified() : null
        );
    }
}
