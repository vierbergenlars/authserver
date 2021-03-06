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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Util\Random;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="EmailAddressRepository")
 * @Gedmo\Loggable
 */
class EmailAddress implements \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Versioned
     */
    private $email;

    /**
     * @ORM\Column(type="boolean")
     * @Gedmo\Versioned
     */
    private $verified = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Gedmo\Versioned
     */
    private $verificationCode;

    /**
     * @ORM\Column(name="primary_mail", type="boolean")
     * @Gedmo\Versioned
     */
    private $primary = false;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="emailAddresses")
     * @ORM\JoinColumn(nullable=false)
     * @Gedmo\Versioned
     * @var User
     */
    private $user;

    public function __construct()
    {
        $this->verificationCode = Random::generateToken();
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->email,
            $this->verified,
            $this->primary,
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->verified,
            $this->primary,
        ) = unserialize($serialized);
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
     * Set email
     *
     * @param  string       $email
     * @return EmailAddress
     */
    public function setEmail($email)
    {
        $this->setVerified(false);
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set verified
     *
     * @param  boolean      $verified
     * @return EmailAddress
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
        if ($verified) {
            $this->verificationCode = null;
        } else {
            $this->verificationCode = Random::generateToken();
        }

        return $this;
    }

    /**
     * Get verified
     *
     * @return boolean
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * Get verificationCode
     *
     * @return string
     */
    public function getVerificationCode()
    {
        return $this->verificationCode;
    }

    /**
     * Set user
     *
     * @param  \App\Entity\User $user
     * @return EmailAddress
     */
    public function setUser(\App\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set verificationCode
     *
     * @param  string       $verificationCode
     * @return EmailAddress
     */
    public function setVerificationCode($verificationCode)
    {
        $this->verificationCode = $verificationCode;

        return $this;
    }

    /**
     * Set primary
     *
     * @param  boolean      $primary
     * @return EmailAddress
     */
    public function setPrimary($primary)
    {
        $this->primary = $primary;

        return $this;
    }

    /**
     * Get primary
     *
     * @return boolean
     */
    public function isPrimary()
    {
        return $this->primary;
    }
}
