<?php
namespace OAuthBundle\Storage;

use OAuth2\Storage\PublicKeyInterface;

class PublicKeyStorage implements PublicKeyInterface
{

    private $publicKeyFile;

    private $privateKeyFile;

    private $signingAlgorithm;

    public function __construct($publicKeyFile, $privateKeyFile, $signingAlgorithm)
    {
        $this->publicKeyFile = $publicKeyFile;
        $this->privateKeyFile = $privateKeyFile;
        $this->signingAlgorithm = $signingAlgorithm;
    }

    public function getEncryptionAlgorithm($client_id = null)
    {
        return $this->signingAlgorithm;
    }

    public function getPublicKey($client_id = null)
    {
        return file_get_contents($this->publicKeyFile);
    }

    public function getPrivateKey($client_id = null)
    {
        return file_get_contents($this->privateKeyFile);
    }
}
