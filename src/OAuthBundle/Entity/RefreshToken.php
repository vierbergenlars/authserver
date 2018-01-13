<?php
namespace OAuthBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RefreshToken
 *
 * @ORM\Table(name="oauth_refresh_token")
 * @ORM\Entity
 */
class RefreshToken
{

    /**
     * @ORM\Column(name="token", type="string", length=40)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $token;

    /**
     * @ORM\Column(name="user_id", type="string", length=100, nullable=true)
     */
    private $userId;

    /**
     * @ORM\Column(name="expires", type="datetime")
     */
    private $expires;

    /**
     * @ORM\Column(name="scope", type="string", length=255, nullable=true)
     */
    private $scope;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OAuth\Client")
     */
    private $client;

    /**
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     *
     * @return mixed
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     *
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     *
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     *
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     *
     * @param mixed $expires
     */
    public function setExpires($expires)
    {
        if (is_numeric($expires)) {
            $this->expires = new \DateTime();
            $this->expires->setTimestamp($expires);
        } else {
            $this->expires = $expires;
        }
    }

    /**
     *
     * @param mixed $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     *
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }
}

