<?php
/*
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2017 Lars Vierbergen
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
namespace App\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OpenIDConnectController extends Controller
{

    /**
     * OpenID Connect configuration endpoint
     * @View
     */
    public function getOpenIDConfigurationAction(Request $request)
    {
        $router = $this->get('router');
        /* @var $router \Symfony\Component\Routing\Router */
        return [
            'issuer' => $router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'authorization_endpoint' => $router->generate('fos_oauth_server_authorize', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'token_endpoint' => $router->generate('fos_oauth_server_token', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'userinfo_endpoint' => $router->generate('api_user_get_info', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'end_session_endpoint' => $router->generate('user_kill_session', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'scopes_supported' => [
                'openid',
                'profile',
                'email'
            ]
        ];
    }

    /**
     * OpenID Connect webfinger endpoint
     *
     * @param Request $request
     */
    public function getWebfingerAction(Request $request)
    {
        if (strpos($request->get('resource', ''), 'acct:') !== 0)
            throw new $this->createNotFoundException('Only acct: resources are supported');

        return [
            'subject' => $request->get('resource'),
            'links' => [
                [
                    'rel' => 'http://openid.net/specs/connect/1.0/issuer',
                    'href' => $router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            ]
        ];
    }
}
