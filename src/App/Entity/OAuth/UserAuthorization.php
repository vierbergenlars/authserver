<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

