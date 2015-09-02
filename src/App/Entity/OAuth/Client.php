<?php

namespace App\Entity\OAuth;

use App\Entity\Group;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="ClientRepository")
 * @Gedmo\Loggable
 */
class Client extends BaseClient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     * @ORM\JoinColumn(nullable=true)
     * @Gedmo\Versioned
     */
    private $groupRestriction;

    /**
     * @ORM\Column(type="boolean")
     * @Gedmo\Versioned
     */
    private $preApproved;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     * @Gedmo\Versioned
     */
    private $preApprovedScopes = array();

    /**
     * @Gedmo\Versioned
     */
    protected $redirectUris = array();

    public function __construct()
    {
        parent::__construct();
        $this->allowedGrantTypes[] = \OAuth2\OAuth2::GRANT_TYPE_REFRESH_TOKEN;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function isPreApproved()
    {
        return $this->preApproved;
    }

    public function setPreApproved($preApproved)
    {
        $this->preApproved = $preApproved;

        return $this;
    }

    public function getPreApprovedScopes()
    {
        return $this->preApprovedScopes;
    }

    public function setPreApprovedScopes(array $preApprovedScopes)
    {
        $this->preApprovedScopes = $preApprovedScopes;

        return $this;
    }

    /**
     * @return Group
     */
    public function getGroupRestriction()
    {
        return $this->groupRestriction;
    }

    /**
     * @param Group $groupRestriction
     * @return Client
     */
    public function setGroupRestriction(Group $groupRestriction = null)
    {
        $this->groupRestriction = $groupRestriction;
        return $this;
    }
}
