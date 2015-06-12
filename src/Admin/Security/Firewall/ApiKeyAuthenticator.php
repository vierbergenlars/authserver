<?php

namespace Admin\Security\Firewall;

use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Doctrine\ORM\EntityRepository;

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
