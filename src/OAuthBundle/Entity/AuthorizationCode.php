<?php
namespace OAuthBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorizationCode
 *
 * @ORM\Table(name="oauth_authorization_code")
 * @ORM\Entity
 */
class AuthorizationCode
{

    /**
     * @ORM\Column(name="code", type="string", length=40)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $code;

    /**
     * @ORM\Column(name="expires", type="datetime")
     */
    private $expires;

    /**
     * @ORM\Column(name="user_id", type="string", length=100, nullable=true)
     */
    private $userId;

    /**
     * @ORM\Column(name="redirect_uri", type="simple_array")
     */
    private $redirectUri;

    /**
     * @ORM\Column(name="scope", type="string", length=255, nullable=true)
     */
    private $scope;

    /**
     * @ORM\Column(name="id_token", type="text", nullable=true)
     */
    private $idToken;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OAuth\Client")
     */
    private $client;

    /**
     *
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     *
     * @return mixed
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
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
    public function getIdToken()
    {
        return $this->idToken;
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
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     *
     * @param mixed $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = explode(' ', $redirectUri);
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
     * @param mixed $idToken
     */
    public function setIdToken($idToken)
    {
        $this->idToken = $idToken;
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

