<?php
namespace OAuthBundle\Storage;

use OAuth2\Storage\PublicKeyInterface;
use OAuthBundle\Service\JwtKeys;
use Jose\KeyConverter\KeyConverter;
use Jose\KeyConverter\RSAKey;

class PublicKeyStorage implements PublicKeyInterface
{

    private $jwtKeys;

    public function __construct(JwtKeys $jwtKeys)
    {
        $this->jwtKeys = $jwtKeys;
    }

    public function getEncryptionAlgorithm($client_id = null)
    {
        return $this->jwtKeys->getSignatureKey()->get('alg');
    }

    public function getPublicKey($client_id = null)
    {
        $rsaKey = new RSAKey($this->jwtKeys->getSignatureKey()->toPublic());
        return $rsaKey->toPEM();
    }

    public function getPrivateKey($client_id = null)
    {
        $rsaKey = new RSAKey($this->jwtKeys->getSignatureKey());
        return $rsaKey->toPEM();
    }
}
