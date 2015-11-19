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

namespace User\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use User\Form\AccountSubmitType;
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

        if ($addr) {

            if (!$addr->isVerified()&&$addr->getVerificationCode() == $verificationCode) {
                $addr->setVerified(true);
                $em->flush();
            }
            if ($addr->isVerified() && !$this->getUser()) {
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
        $form  = $this->createForm(new AccountSubmitType(), array('user'=>$request->query->get('user', '')));
        $flash = $this->get('braincrafted_bootstrap.flash');
        $mailer = $this->get('app.mailer.user.verify_email');

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress');

        if ($form->isValid()) {
            $user = $form->get('user')->getData();
            /* @var $user User */
            $addr = $user->getPrimaryEmailAddress();
            if(!$addr) {
                $flash->error('This account does not have an email address associated.');
            } else if (!$addr->isVerified()) {
                $addr->setVerified(false);

                if ($mailer->sendMessage($addr->getEmail(), $addr)) {
                    $em->flush();
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
