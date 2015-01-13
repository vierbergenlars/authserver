<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;

class UserController extends Controller
{
    /**
     * @View
     * @throws AccessDeniedException
     */
    public function getAction()
    {
        if(!($user = $this->getUser())) {
            throw new AccessDeniedException();
        }
        return array(
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'name' => $user->getDisplayName(),
            'groups' => array_keys($user->_getAllGroupNames()),
        );
    }
}
