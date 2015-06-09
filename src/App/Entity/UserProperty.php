<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_properties")
 * @Gedmo\Loggable
 */
class UserProperty
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     */
    private $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userProperties")
     * @Gedmo\Versioned
     */
    private $user;

    /**
     * @var Property
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="userProperties")
     * @Gedmo\Versioned
     */
    private $property;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $data;

    public function __construct(User $user, Property $property)
    {
        $this->user = $user;
        $this->property = $property;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
