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
namespace OAuthBundle\Controller;

use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OpenIDConnectController extends Controller
{

    /**
     * OpenID Connect configuration endpoint
     */
    public function getOpenIDConfigurationAction(Request $request)
    {
        $router = $this->get('router');
        /* @var $router \Symfony\Component\Routing\Router */
        return [
            'issuer' => $this->container->getParameter('oauth_issuer'),
            'authorization_endpoint' => $router->generate('oauth_authorize_handle', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'token_endpoint' => $router->generate('oauth_token', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'userinfo_endpoint' => $router->generate('oauth_userinfo', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'jwks_uri' => $router->generate('oauth_wellknown_jwks', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'response_types_supported' => [
                "code",
                "token",
                "id_token",
                "code token",
                "code id_token",
                "token id_token",
                "code token id_token"
            ],
            'subject_types_supported' => [
                'public'
            ],
            'id_token_signing_alg_values_supported' => [
                'RS256'
            ],
            'end_session_endpoint' => $router->generate('user_kill_session', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'scopes_supported' => [
                'openid',
                'profile',
                'email'
            ],
            'claims_supported' => [
                'iss',
                'sub',
                'name',
                'preferred_username',
                'email',
                'email_verified'
            ]
        ];
    }

    /**
     * OpenID Connect webfinger endpoint
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
                    'href' => $this->container->getParameter('oauth_issuer')

                ]
            ]
        ];
    }

    /**
     * JWK set endpoint
     */
    public function getJwksAction()
    {
        $jwtKeys = $this->get('oauth.jwt_keys');
        /* @var $jwtKeys \OAuthBundle\Service\JwtKeys */
        return $jwtKeys->getKeyset()->jsonSerialize();
    }
}
