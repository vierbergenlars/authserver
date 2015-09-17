<?php

namespace User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
    public function killSessionAction()
    {
        if($this->isGranted('ROLE_PREVIOUS_ADMIN')) {
            $this->addFlash('info', 'Your session has not been terminated. Your impersonation of another user has been terminated instead.');
            return $this->redirectToRoute('user_profile', array('_switch_user' => '_exit'));
        } else {
            return $this->redirectToRoute('logout');
        }
    }
}
