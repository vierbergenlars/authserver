<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Property
 *
 * @ORM\Table(name="properties")
 * @ORM\Entity(repositoryClass="PropertyRepository")
 * @Gedmo\Loggable
 */
class Property
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=25, unique=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="display_name", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $displayName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="user_editable", type="boolean")
     * @Gedmo\Versioned
     */
    private $userEditable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="required", type="boolean")
     * @Gedmo\Versioned
     */
    private $required;

    /**
     * @var string
     *
     * @ORM\Column(name="validation_regex", type="text")
     * @Gedmo\Versioned
     */
    private $validationRegex = '/^.*$/';

    /**
     * @var UserProperty[]
     *
     * @ORM\OneToMany(targetEntity="UserProperty", mappedBy="property", cascade={"REMOVE"})
     */
    private $userProperties;

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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get userEditable
     *
     * @return boolean
     */
    public function isUserEditable()
    {
        return $this->userEditable;
    }

    /**
     * Get required
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Set name
     *
     * @param  string       $name
     * @return UserProperty
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set userEditable
     *
     * @param  boolean      $userEditable
     * @return UserProperty
     */
    public function setUserEditable($userEditable)
    {
        $this->userEditable = $userEditable;

        return $this;
    }

    /**
     * Set required
     *
     * @param  boolean      $required
     * @return UserProperty
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Set displayName
     *
     * @param  string   $displayName
     * @return Property
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

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

    public function getValidationRegex()
    {
        return $this->validationRegex;
    }

    public function setValidationRegex($validationRegex)
    {
        $this->validationRegex = $validationRegex;

        return $this;
    }
}
