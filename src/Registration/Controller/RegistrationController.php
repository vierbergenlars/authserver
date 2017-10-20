<?php

namespace Registration\Controller;

use Registration\RegistrationHandler\RegistrationHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistrationController extends Controller
{
    /**
     * @Template
     */
    public function registerAction(Request $request)
    {
        if(!$this->has('registration.handler'))
            throw $this->createNotFoundException();
        $registrationHandler = $this->get('registration.handler');
        /* @var RegistrationHandler $registrationHandler */
        if($form = $registrationHandler->handleRequest($request))
            return [
                'form' =>$form,
                'registration_message' => $this->getParameter('registration.message'),
                'temporary_user' => $this->isGranted('ROLE_TEMPORARY_USER') ? $this->getUser() : null
            ];
        return $this->redirectToRoute('app_login');
    }
}
