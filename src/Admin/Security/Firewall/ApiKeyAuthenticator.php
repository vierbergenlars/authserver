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

namespace Admin\Security\Firewall;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{
    protected $apiProvider;

    public function __construct(EntityRepository $apiProvider)
    {
        $this->apiProvider = $apiProvider;
    }

    public function createToken(Request $request, $providerKey)
    {
        if (strpos($request->getUser(), '-apikey-') !== 0||!$request->getPassword()) {
            throw new BadCredentialsException('No API key found');
        }

        return new PreAuthenticatedToken(
            'anon.',
            array(substr($request->getUser(), 8), $request->getPassword()),
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $apiKey = $token->getCredentials();
        if(strpos($apiKey[0], ';') !== false) {
            list($apiKeyId,) = explode(';', $apiKey[0]);
        } else {
            $apiKeyId = $apiKey[0];
        }
        $key = $this->apiProvider->find($apiKeyId);

        if (!$key) {
            throw new AuthenticationException(
                sprintf('API Key "%s" does not exist.', $apiKey[0])
            );
        }

        if ($key->getSecret() !== $apiKey[1]) {
            throw new AuthenticationException(
                sprintf('Bad secret for API Key "%s"', $apiKey[0])
            );
        }

        $scopes = $key->getScopes();
        $roles = array('ROLE_API');
        foreach ($scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_'.strtoupper($scope);
        }

        return new PreAuthenticatedToken(
            '-apikey-'.$apiKey[0],
            $apiKey,
            $providerKey,
            $roles
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}
