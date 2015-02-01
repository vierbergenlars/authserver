<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user_properties")
 */
class UserProperty {
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userProperties")
     * @ORM\Id
     */
    private $user;
    
    /**
     * @var Property
     * @ORM\ManyToOne(targetEntity="Property")
     * @ORM\Id
     */
    private $property;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $data;
    
    public function __construct(User $user, Property $property) {
        $this->user = $user;
        $this->property = $property;
    }

    public function getUser() {
        return $this->user;
    }

    public function getProperty() {
        return $this->property;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }
}
