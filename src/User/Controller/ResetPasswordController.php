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
use App\Entity\UserRepository;
use App\Mail\PrimedTwigMailer;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use User\Form\AccountSubmitType;
use User\Form\AddPasswordType;


class ResetPasswordController extends Controller
{
    /**
     * @Template
     */
    public function forgotPasswordAction(Request $request)
    {
        $form  = $this->createForm(new AccountSubmitType(), array('user'=>$request->query->get('user', '')));
        $flash = $this->get('braincrafted_bootstrap.flash');
        $mailer = $this->get('app.mailer.user.reset_password');
        /* @var $flash FlashMessage */
        /* @var $mailer PrimedTwigMailer */

        if(!$this->isGranted('IS_AUTHENTICATED_ANONYMOUSLY')&&!$this->isGranted('ROLE_ADMIN')) {
            $flash->alert('You are already logged in. If you forgot your password, please log out before trying to reset it.');
            return $this->redirectToRoute('user_profile');
        }

        $form->handleRequest($request);
        if($form->isValid()) {
            $user = $form->get('user')->getData();
            /* @var $user User */
            if($user->getPasswordEnabled() == 0) {
                $flash->error('Your account does not have password authentication enabled.');
            } else {
                $user->generatePasswordResetToken();
                $this->getDoctrine()->getManagerForClass('AppBundle:User')->flush();
                if(!$mailer->sendMessage($user, $user)) {
                    $flash->error('Could not send you a message. Is your email address already verified?');
                } else {
                    $flash->success('Password reset instructions have been emailed to you.');
                }
            }
            return $this->redirectToRoute('app_login');
        }

        return $form;
    }

    /**
     * @Template
     */
    public function resetPasswordAction(Request $request, $username, $verificationCode)
    {

        $form = $this->createForm(new AddPasswordType());
        $flash = $this->get('braincrafted_bootstrap.flash');
        /* @var $flash FlashMessage */

        if(!$this->isGranted('IS_AUTHENTICATED_ANONYMOUSLY')) {
            $flash->alert('You are already logged in. Log out before resetting your password.');
            return $this->redirectToRoute('user_profile');
        }

        $user = $this->getDoctrine()->getRepository('AppBundle:User')
            ->findOneBy(array('username'=>$username));
        /* @var $user User */
        if($user === null||$user->getPasswordResetToken() !== $verificationCode) {
            $flash->error('This password reset code is no longer valid, or this user does no longer exist.');
        } else if($user->getPasswordEnabled() == 0) {
            $flash->error('This account does not have password authentication enabled.');
        } else {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $user->setPassword(
                    $this->get('security.encoder_factory')
                         ->getEncoder('App\Entity\User')
                         ->encodePassword($form->get('password')->getData(), null)
                );
                $user->setPasswordEnabled(1);
                $user->clearPasswordResetToken();
                $this->getDoctrine()->getManagerForClass('AppBundle:User')->flush();
                $flash->success('Your password has been changed. You can now log in with your new password.');
            } else {
                return $form;
            }
        }
        return $this->redirectToRoute('app_login');
    }
}