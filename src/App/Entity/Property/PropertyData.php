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

namespace App\Entity\Property;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PropertyData
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class PropertyData
{
    /**
     * @var PropertyNamespace
     *
     * @ORM\ManyToOne(targetEntity="PropertyNamespace")
     * @ORM\Id
     */
    private $namespace;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ORM\Id
     */
    private $name;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\Id
     */
    private $user;

    /**
     * @var resource
     *
     * @ORM\Column(name="data", type="blob")
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(name="content_type", type="string")
     */
    private $contentType;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return PropertyData
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

    /**
     * Set data
     *
     * @param string $data
     *
     * @return PropertyData
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return resource
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set namespace
     *
     * @param PropertyNamespace $namespace
     *
     * @return PropertyData
     */
    public function setNamespace(PropertyNamespace $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Get namespace
     *
     * @return PropertyNamespace
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return PropertyData
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     * @return PropertyData
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }
}
