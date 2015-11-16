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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Group
 *
 * @ORM\Table(name="auth_group")
 * @ORM\Entity(repositoryClass="GroupRepository")
 * @Gedmo\Loggable
 */
class Group
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=255, unique=true)
     * @Gedmo\Versioned
     */
    private $displayName;

    /**
     * @var User[]
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups", fetch="EXTRA_LAZY")
     */
    private $members;

    /**
     * The groups that are member of this group
     * @var Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", mappedBy="groups", fetch="EXTRA_LAZY")
     */
    private $memberGroups;

    /**
     * The groups this group is member of
     * @var Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="memberGroups", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="group_group",
     *      joinColumns={@ORM\JoinColumn(name="group_target", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_source", referencedColumnName="id")}
     * )
     */
    private $groups;

    /**
     * Marks the group as visible to external services
     * @var boolean
     *
     * @ORM\Column(name="exportable", type="boolean")
     * @Gedmo\Versioned
     */
    private $exportable = true;

    /**
     * Marks the group as joinable by normal users
     * @var boolean
     *
     * @ORM\Column(name="user_joinable", type="boolean")
     * @Gedmo\Versioned
     */
    private $userJoinable = false;

    /**
     * Marks the group as leaveable by normal users
     * @var boolean
     *
     * @ORM\Column(name="user_leaveable", type="boolean")
     * @Gedmo\Versioned
     */
    private $userLeaveable = false;

    /**
     * Marks the group as not containing any users
     *
     * This is an advisory flag only, adding users will not be blocked
     * @var boolean
     *
     * @ORM\Column(name="no_users", type="boolean")
     * @Gedmo\Versioned
     */
    private $noUsers = false;

    /**
     * Marks the group as not containing any groups
     *
     * This is an advisory flag only, adding groups will not be blocked
     * @var boolean
     *
     * @ORM\Column(name="no_groups", type="boolean")
     * @Gedmo\Versioned
     */
    private $noGroups = false;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->name;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getMigrateId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return Group
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
     * Set displayName
     *
     * @param  string $name
     * @return Group
     */
    public function setDisplayName($name)
    {
        $this->displayName = $name;

        return $this;
    }

    /**
     * Get displayName
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Add members
     *
     * @param  \App\Entity\User $members
     * @return Group
     */
    public function addMember(\App\Entity\User $members)
    {
        $this->members[] = $members;

        return $this;
    }

    /**
     * Remove members
     *
     * @param \App\Entity\User $members
     */
    public function removeMember(\App\Entity\User $members)
    {
        $this->members->removeElement($members);
    }

    /**
     * Get members
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Add memberGroups
     *
     * DO NOT USE
     * @internal
     *
     * @param  \App\Entity\Group $memberGroups
     * @return Group
     */
    public function addMemberGroup(\App\Entity\Group $memberGroups)
    {
        $this->memberGroups[] = $memberGroups;

        return $this;
    }

    /**
     * Remove memberGroups
     *
     * DO NOT USE
     * @internal
     *
     * @param \App\Entity\Group $memberGroups
     */
    public function removeMemberGroup(\App\Entity\Group $memberGroups)
    {
        $this->memberGroups->removeElement($memberGroups);
    }

    /**
     * Get memberGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMemberGroups()
    {
        return $this->memberGroups;
    }

    /**
     * Add groups
     *
     * @param  \App\Entity\Group $groups
     * @return Group
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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->members = new \Doctrine\Common\Collections\ArrayCollection();
        $this->memberGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getGroupsRecursive(&$groups = array())
    {
        if (!isset($groups[$this->getName()])) {
            $groups[$this->getName()] = $this;
            foreach ($this->groups as $group) {
                $group->getGroupsRecursive($groups);
            }
        }
        return array_values($groups);
    }

    public function getMemberGroupsRecursive(&$groups = array())
    {
        if (!isset($groups[$this->getName()])) {
            $groups[$this->getName()] = $this;
            foreach ($this->memberGroups as $group) {
                $group->getMemberGroupsRecursive($groups);
            }
        }
        return array_values($groups);
    }

    /**
     * Set exportable
     *
     * @param  boolean $exportable
     * @return Group
     */
    public function setExportable($exportable)
    {
        $this->exportable = $exportable;

        return $this;
    }

    /**
     * Get exportable
     *
     * @return boolean
     */
    public function isExportable()
    {
        return $this->exportable;
    }

    /**
     * Get exportable
     *
     * @return boolean
     */
    public function getExportable()
    {
        return $this->exportable;
    }

    /**
     * Set noUsers
     *
     * @param  boolean $noUsers
     * @return Group
     */
    public function setNoUsers($noUsers)
    {
        $this->noUsers = $noUsers;

        return $this;
    }

    /**
     * Get noUsers
     *
     * @return boolean
     */
    public function getNoUsers()
    {
        return $this->noUsers;
    }

    /**
     * Set noGroups
     *
     * @param  boolean $noGroups
     * @return Group
     */
    public function setNoGroups($noGroups)
    {
        $this->noGroups = $noGroups;

        return $this;
    }

    /**
     * Get noGroups
     *
     * @return boolean
     */
    public function getNoGroups()
    {
        return $this->noGroups;
    }

    /**
     * @return boolean
     */
    public function isUserJoinable()
    {
        return $this->userJoinable;
    }

    /**
     * @param boolean $userJoinable
     * @return $this
     */
    public function setUserJoinable($userJoinable)
    {
        $this->userJoinable = $userJoinable;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUserLeaveable()
    {
        return $this->userLeaveable;
    }

    /**
     * @param boolean $userLeaveable
     *
     * @return $this
     */
    public function setUserLeaveable($userLeaveable)
    {
        $this->userLeaveable = $userLeaveable;
        return $this;
    }



}
