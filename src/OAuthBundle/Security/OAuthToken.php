<?php
namespace OAuthBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class OAuthToken extends AbstractToken
{

    public function __construct(array $token, $providerKey)
    {
        parent::__construct(array_map(function ($scope) {
            return 'SCOPE_' . strtoupper($scope);
        }, explode(' ', $token['scope'])));
        $this->clientId = $token['client_id'];
        $this->providerKey = $providerKey;
        $this->token = $token;
    }

    private $providerKey;

    /**
     *
     * @var string
     */
    protected $token;

    /**
     *
     * @var int
     */
    protected $clientId;

    public function getCredentials()
    {
        return $this->token;
    }

    /**
     *
     * @return number
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     *
     * @return mixed
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }
}