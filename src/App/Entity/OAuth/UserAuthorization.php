<?php

namespace App\Entity\OAuth;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserAuthorization
 *
 * @ORM\Table(name="user_oauthclient")
 * @ORM\Entity
 */
class UserAuthorization
{
    /**
     * @var array
     *
     * @ORM\Column(name="scopes", type="simple_array")
     */
    private $scopes;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="Client", fetch="EAGER")
     * @ORM\JoinColumn(name="client_id", nullable=false)
     * @ORM\Id
     */
    private $client;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="authorizedApplications", fetch="EAGER")
     * @ORM\JoinColumn(name="user_id", nullable=false)
     * @ORM\Id
     */
    private $user;

    public function __construct(Client $client, User $user)
    {
        $this->client = $client;
        $this->user   = $user;
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
     * @param array $scopes
     *
     * @return UserAuthorization
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
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
