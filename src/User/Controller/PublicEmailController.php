<?php

namespace User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use User\Form\AccountResendVerificationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


class PublicEmailController extends Controller
{
    /**
     * @Template
     */
    public function verifyEmailAction($id, $verificationCode)
    {
        $em    = $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress');
        $addr  = $em->find('AppBundle:EmailAddress', $id);
        $flash = $this->get('braincrafted_bootstrap.flash');

        if($addr) {

            if(!$addr->isVerified()&&$addr->getVerificationCode() == $verificationCode) {
                $addr->setVerified(true);
                $em->flush($addr);
            }
            if($addr->isVerified() && !$this->getUser()) {
                $flash->success('Your email address has been verified, and your account has been activated. You can now log in.');
                return $this->redirect($this->generateUrl('app_login'));
            }
        }
        return $addr;
    }

    /**
     * @Template
     */
    public function resendVerificationAction(Request $request)
    {
        $form  = $this->createForm(new AccountResendVerificationType(), array('user'=>$request->query->get('user', '')));
        $flash = $this->get('braincrafted_bootstrap.flash');
        $mailer = $this->get('app.mailer.user.verify_email');

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress');


        if($form->isValid()) {
            $user = $form->get('user')->getData();
            $addr = $user->getPrimaryEmailAddress();
            if(!$addr->isVerified()) {
                $addr->setVerified(false);

                if($mailer->sendMessage($addr->getEmail(), $addr)) {
                    $em->flush($addr);
                    $flash->success('A new confirmation email has been sent');
                } else {
                    $flash->error('We are having some troubles sending you a verification mail. Please try again later.');
                }
            } else {
                $flash->error('Email address has already been verified');
            }

            return $this->redirect($this->generateUrl('app_login'));
        }

        return $form;
    }
}
