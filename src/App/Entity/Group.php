<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Group
 *
 * @ORM\Table(name="auth_group")
 * @ORM\Entity(repositoryClass="App\Doctrine\EntityRepository")
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
     */
    private $name;

    /**
     * @var User[]
     *
     * @ORM\ManyToMany(targetEntity="User", inversedBy="groups")
     */
    private $members;


    /**
     * The groups that are member of this group
     * @var Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="groups")
     */
    private $memberGroups;

    /**
     * The groups this group is member of
     * @var Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", mappedBy="memberGroups")
     */
    private $groups;

    /**
     * Marks the group as visible to external services
     * @var boolean
     *
     * @ORM\Column(name="exportable", type="boolean")
     */
    private $exportable;

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
     * @return Group_
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
     * Add members
     *
     * @param \App\Entity\User $members
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
     * @param \App\Entity\Group $memberGroups
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
     * @param \App\Entity\Group $groups
     * @return Group
     */
    public function addGroup(\App\Entity\Group $groups)
    {
        $this->groups[] = $groups;
        $groups->addMemberGroup($this);

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
        $groups->removeMemberGroup($this);
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
        $this->exportable = true;
    }

    public function _getAllGroupNames()
    {
        $groups = array();
        if($this->isExportable()) {
            $groups[$this->getName()] = true;
        }
        foreach($this->groups as $group) {
            $groups = array_merge($groups, $group->_getAllGroupNames());
        }
        return $groups;
    }


    /**
     * Set exportable
     *
     * @param boolean $exportable
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
}
