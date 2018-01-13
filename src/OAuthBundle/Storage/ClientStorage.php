<?php
namespace OAuthBundle\Storage;

use OAuth2\Storage\ClientCredentialsInterface;
use Doctrine\ORM\EntityManagerInterface;

class ClientStorage implements ClientCredentialsInterface
{

    /**
     *
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     *
     * @param string $client_id
     * @return null|\App\Entity\OAuth\Client
     */
    private function getClient($client_id)
    {
        $parts = explode('_', $client_id);
        if (count($parts) != 2) {
            return false;
        }
        $client = $this->em->find('AppBundle:OAuth\\Client', $parts[0]);
        /* @var $client \App\Entity\OAuth\Client */
        if ($client->getPublicId() !== $client_id) {
            return null;
        }
        return $client;
    }

    public function getClientDetails($client_id)
    {
        $client = $this->getClient($client_id);
        if (!$client)
            return false;
        return [
            'redirect_uri' => implode(' ', $client->getRedirectUris()),
            'grant_types' => $client->getAllowedGrantTypes()
        ];
    }

    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $client = $this->getClientDetails($client_id);

        if (!$client) {
            return false;
        }

        if (empty($client['grant_types'])) {
            return true;
        }

        if (in_array($grant_type, $client['grant_types'])) {
            return true;
        }

        return false;
    }

    public function checkClientCredentials($client_id, $client_secret = null)
    {
        $client = $this->getClient($client_id);
        if (!$client) {
            return false;
        }

        return $client->getSecret() === $client_secret;
    }

    public function getClientScope($client_id)
    {
        return implode(' ', $this->getClient($client_id)->getMaxScopes());
    }

    public function isPublicClient($client_id)
    {
        return false;
    }
}
