<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\OAuthServerBundle\Util\Random;

/**
 * @ORM\Entity
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
     */
    private $email;

    /**
     * @ORM\Column(type="boolean")
     */
    private $verified = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $verificationCode;

    /**
     * @ORM\Column(name="primary_mail", type="boolean")
     */
    private $primary = false;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
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
     * @param string $email
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
     * @param boolean $verified
     * @return EmailAddress
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
        if($verified) {
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
     * @param \App\Entity\User $user
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
     * @param string $verificationCode
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
     * @param boolean $primary
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
