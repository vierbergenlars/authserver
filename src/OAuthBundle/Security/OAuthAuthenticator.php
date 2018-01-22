<?php
namespace OAuthBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use OAuth2\Server;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use OAuth2\Model\OAuth2Token;

class OAuthAuthenticator implements SimplePreAuthenticatorInterface
{

    /**
     *
     * @var Server
     */
    private $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof OAuthToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $accessToken = $token->getCredentials();

        $user = $userProvider->loadUserByUsername($accessToken['user_id']);

        if (!$user) {
            throw new UsernameNotFoundException();
        }

        $token->setUser($user);
        $token->setAuthenticated(true);

        return $token;
    }

    public function createToken(Request $request, $providerKey)
    {
        $response = new ErrorThrowingResponse();
        $accessTokenData = $this->server->getAccessTokenData(\OAuth2\HttpFoundationBridge\Request::createFromRequest($request), $response);
        if (!$accessTokenData && !$response->getParameter('error')) {
            return null;
        }
        $response->throwIfError();

        $token = new OAuthToken($accessTokenData, $providerKey);

        return $token;
    }
}