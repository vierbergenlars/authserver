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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PropertyNamespace
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class PropertyNamespace
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public_readable", type="boolean")
     */
    private $publicReadable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public_writeable", type="boolean")
     */
    private $publicWriteable;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\OAuth\Client")
     * @ORM\JoinTable(name="propertynamespace_oauthclient_readers")
     */
    private $readers;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\OAuth\Client")
     * @ORM\JoinTable(name="propertynamespace_oauthclient_writers")
     */
    private $writers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->readers = new ArrayCollection();
        $this->writers = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     *
     * @return PropertyNamespace
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
     * Set publicReadable
     *
     * @param boolean $publicReadable
     *
     * @return PropertyNamespace
     */
    public function setPublicReadable($publicReadable)
    {
        $this->publicReadable = $publicReadable;

        return $this;
    }

    /**
     * Get publicReadable
     *
     * @return boolean
     */
    public function isPublicReadable()
    {
        return $this->publicReadable;
    }

    /**
     * Set publicWriteable
     *
     * @param boolean $publicWriteable
     *
     * @return PropertyNamespace
     */
    public function setPublicWriteable($publicWriteable)
    {
        $this->publicWriteable = $publicWriteable;

        return $this;
    }

    /**
     * Get publicWriteable
     *
     * @return boolean
     */
    public function isPublicWriteable()
    {
        return $this->publicWriteable;
    }

    /**
     * Add reader
     *
     * @param \App\Entity\OAuth\Client $reader
     *
     * @return PropertyNamespace
     */
    public function addReader(\App\Entity\OAuth\Client $reader)
    {
        $this->readers[] = $reader;

        return $this;
    }

    /**
     * Remove reader
     *
     * @param \App\Entity\OAuth\Client $reader
     */
    public function removeReader(\App\Entity\OAuth\Client $reader)
    {
        $this->readers->removeElement($reader);
    }

    /**
     * Get readers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReaders()
    {
        return $this->readers;
    }

    /**
     * Add writer
     *
     * @param \App\Entity\OAuth\Client $writer
     *
     * @return PropertyNamespace
     */
    public function addWriter(\App\Entity\OAuth\Client $writer)
    {
        $this->writers[] = $writer;

        return $this;
    }

    /**
     * Remove writer
     *
     * @param \App\Entity\OAuth\Client $writer
     */
    public function removeWriter(\App\Entity\OAuth\Client $writer)
    {
        $this->writers->removeElement($writer);
    }

    /**
     * Get writers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWriters()
    {
        return $this->writers;
    }
}
