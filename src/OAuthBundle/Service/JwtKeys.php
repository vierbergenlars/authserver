<?php
namespace OAuthBundle\Service;

use Jose\Factory\JWKFactory;

class JwtKeys
{

    private $keyset;

    public function __construct($keyStorage)
    {
        $this->keyset = JWKFactory::createRotatableKeySet($keyStorage, [
            'kty' => 'RSA',
            'size' => 4096,
            'alg' => 'RS256',
            'use' => 'sig'
        ], 1, 3600);
    }

    /**
     *
     * @return \Jose\Object\JWKInterface
     */
    public function getSignatureKey()
    {
        return $this->keyset->selectKey('sig', 'RS256');
    }

    /**
     *
     * @return \Jose\Object\JWKSetInterface
     */
    public function getKeyset()
    {
        return JWKFactory::createPublicKeySet($this->keyset);
    }
}