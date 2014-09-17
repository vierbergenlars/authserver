<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Routing\ClassResourceInterface;

class MembershipController extends Controller
{
    /**
     * @View
     * @throws AccessDeniedException
     */
    public function cgetAction()
    {
        if(!($user = $this->getUser())) {
            throw new AccessDeniedException();
        }
        return array_keys($user->_getAllGroupNames());
    }
}