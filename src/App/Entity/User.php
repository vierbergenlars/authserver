<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="auth_users")
 * @ORM\Entity(repositoryClass="UserRepository")
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(name="roles", type="string")
     */
    private $role;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @var Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="members", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="group_user")
     */
    private $groups;

    /**
     * @var App\Entity\OAuth\Client[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\OAuth\Client", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="user_oauthclient")
     */
    private $authorizedApplications;

    public function __construct()
    {
        $this->role = 'ROLE_USER';
        $this->isActive = true;
        $this->groups = new ArrayCollection();
        $this->authorizedApplications = new ArrayCollection();
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getSalt()
    {
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return array($this->role);
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    public function eraseCredentials()
    {
    }

    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
        ));
    }

    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
        ) = unserialize($serialized);
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    public function setEnabled($enabled)
    {
        $this->isActive = $enabled;
        return $this;
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
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        if($password) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
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
     * Add groups
     *
     * @param \App\Entity\Group $groups
     * @return User
     */
    public function addGroup(\App\Entity\Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \App\Entity\Group $groups
     */
    public function removeGroup(\App\Entity\Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    public function _getAllGroupNames()
    {
        $groups = array();
        foreach($this->groups as $group) {
            $groups = array_merge($groups, $group->_getAllGroupNames());
        }
        return $groups;
    }

    /**
     * Add authorizedApplications
     *
     * @param \App\Entity\OAuth\Client $authorizedApplications
     * @return User
     */
    public function addAuthorizedApplication(\App\Entity\OAuth\Client $authorizedApplications)
    {
        $this->authorizedApplications[] = $authorizedApplications;

        return $this;
    }

    /**
     * Remove authorizedApplications
     *
     * @param \App\Entity\OAuth\Client $authorizedApplications
     */
    public function removeAuthorizedApplication(\App\Entity\OAuth\Client $authorizedApplications)
    {
        $this->authorizedApplications->removeElement($authorizedApplications);
    }

    /**
     * Get authorizedApplications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuthorizedApplications()
    {
        return $this->authorizedApplications;
    }
}
