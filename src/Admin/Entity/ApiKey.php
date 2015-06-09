<?php

namespace Admin\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Util\Random;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ApiKey
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="ApiKeyRepository")
 * @Gedmo\Loggable
 */
class ApiKey
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array
     *
     * @ORM\Column(name="scopes", type="simple_array")
     * @Gedmo\Versioned
     */
    private $scopes = array();

    /**
     * @var string
     *
     * @ORM\Column(name="secret", type="string")
     */
    private $secret;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     * @Gedmo\Versioned
     */
    private $name;

    public function __construct()
    {
        $this->secret = Random::generateToken();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set scopes
     *
     * @param  array  $scopes
     * @return ApiKey
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * Get scopes
     *
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * Get secret
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return ApiKey
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
