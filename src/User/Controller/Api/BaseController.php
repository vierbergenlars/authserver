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

use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{

    protected function getToken()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return false;
        }

        return $token;
    }

    protected function isOAuth()
    {
        return $this->getToken() instanceof OAuthToken;
    }

    protected function isGrantedScope($scope)
    {
        if (!$this->isOAuth())
            return true;
        return $this->isGranted('ROLE_' . strtoupper($scope));
    }

    protected function denyAccessUnlessGrantedScope($scope)
    {
        if (!$this->isGrantedScope($scope))
            throw $this->createAccessDeniedException('OAuth scope ' . $scope . ' is required to access this resource.');
    }

    protected function denyAccessIfGrantedScope($scope)
    {
        if ($this->isOAuth() && $this->isGrantedScope($scope))
            throw $this->createAccessDeniedException('OAuth scope ' . $scope . ' is forbidden to access this resource.');
    }
}