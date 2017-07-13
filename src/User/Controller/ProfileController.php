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

use App\Entity\EmailAddress;
use App\Entity\Group;
use App\Entity\OAuth\AccessToken;
use App\Entity\OAuth\RefreshToken;
use App\Entity\OAuth\UserAuthorization;
use App\Entity\User;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use User\Form\AddGroupType;
use User\Form\AddPasswordType;
use User\Form\ChangePasswordType;
use User\Form\DeleteAuthorizedAppType;
use User\Form\DeleteGroupType;
use User\Form\EditEmailAddressType;
use User\Form\EmailAddressType;

class ProfileController extends Controller
{
    /**
     * @View
     */
    public function indexAction()
    {
        $hasJoinableGroupsBuilder = $this->getDoctrine()
            ->getManagerForClass(Group::class)
            ->getRepository(Group::class)
            ->createQueryBuilder('g')
            ->select('COUNT(g)')
            ->where('g.noUsers = false AND g.userJoinable = true');
        if($this->getUser()->getGroups()->count() > 0)
            $hasJoinableGroupsBuilder->andWhere('g NOT IN(:groups)')
                ->setParameter('groups', $this->getUser()->getGroups());
        $hasJoinableGroups = $hasJoinableGroupsBuilder->getQuery()
            ->getSingleScalarResult() > 0;
        return array(
            'data'=>$this->getUser(),
            'form' => array(
                'add_email' => $this->createForm(EmailAddressType::class)->createView(),
                'add_group' => $hasJoinableGroups?$this->createForm(AddGroupType::class)->createView():null,
            ),
        );
    }

    /**
     * @View
     * Internal action, not exposed in a route
     */
    public function removeAuthorizedAppAction($appId)
    {
        return $this->createForm(DeleteAuthorizedAppType::class, array('id'=>$appId));
    }

    public function deleteAuthorizedAppAction(Request $request)
    {
        $form = $this->createForm(DeleteAuthorizedAppType::class);
        $user = $this->getUser();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $appId = $form->get('id')->getData();
            $userAuthorization = $this->getDoctrine()
                ->getRepository(UserAuthorization::class)
                ->findOneBy(array(
                    'client' => $appId,
                    'user' => $user,
                ));
            /* @var $userAuthorization UserAuthorization */

            if ($userAuthorization) {
                $this->getDoctrine()->getManager()->beginTransaction();
                $this->getDoctrine()->getRepository(RefreshToken::class)
                        ->createQueryBuilder('t')
                        ->delete()
                        ->where('t.client = :client AND t.user = :user')
                        ->setParameter('client', $userAuthorization->getClient())
                        ->setParameter('user', $user)
                        ->getQuery()
                        ->execute();
                $this->getDoctrine()->getRepository(AccessToken::class)
                        ->createQueryBuilder('t')
                        ->delete()
                        ->where('t.client = :client AND t.user = :user')
                        ->setParameter('client', $userAuthorization->getClient())
                        ->setParameter('user', $user)
                        ->getQuery()
                        ->execute();
                $em = $this->getDoctrine()->getManagerForClass(UserAuthorization::class);
                $em->remove($userAuthorization);
                $em->flush();

                $this->getDoctrine()->getManager()->commit();
                $this->getFlash()->success('Authorized application has been removed');

                return $this->redirectToProfile();
            }
        }

        $this->getFlash()->error('Error removing authorized application');

        return $this->redirectToProfile();
    }

    /**
     * @View
     * Internal action, not exposed in a route
     */
    public function editEmailAddressesAction(EmailAddress $addr)
    {
        return array('form'=>$this->createForm(EditEmailAddressType::class)->createView(), 'data'=>$addr);
    }

    public function putEmailAddressesAction(EmailAddress $addr, Request $request)
    {
        if($addr->getUser() !== $this->getUser())
            throw $this->createNotFoundException();
        $mailer = $this->get('app.mailer.user.verify_email');

        $form  = $this->createForm(EditEmailAddressType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            switch ($form->getClickedButton()->getName()) {
                case 'sendConfirmation':
                    if (!$addr->isVerified()) {
                        $addr->setVerified(false);
                        if ($mailer->sendMessage($addr->getEmail(), $addr)) {
                            $this->getFlash()->success('A new confirmation email has been sent');
                        } else {
                            $this->getFlash()->error('We are having some troubles sending you a verification mail. Please try again later.');
                        }

                    }
                    break;
                case 'setPrimary':
                    if ($addr->isVerified()) {
                        $this->getUser()
                            ->getEmailAddresses()
                            ->map(function (EmailAddress $e) {
                                if($e->isPrimary())
                                    $e->setPrimary(false);
                            });

                        $addr->setPrimary(true);
                        $this->getFlash()->success('Primary email address updated');
                    } else {
                        $this->getFlash()->error('Please verify this email address before setting it as primary email address');
                    }
                    break;
                case 'remove':
                    if (!$addr->isPrimary()) {
                        $this->getDoctrine()
                                ->getManagerForClass(EmailAddress::class)
                                ->remove($addr);
                        $this->getFlash()->success('Email address removed');
                    } else {
                        $this->getFlash()->error('Your primary email address cannot be removed. You must first set another verified email address as your primary email address.');
                    }
                    break;
                default:
                    // Should never happen
                    $this->getFlash()->error('Internal error: Unknown button pressed');
            }
            $this->getDoctrine()
                    ->getManagerForClass(EmailAddress::class)
                    ->flush();
        } else {
            $this->getFlash()->error('Error modifying email address');
        }

        return $this->redirectToProfile();
    }

    public function postEmailAddressesAction(Request $request)
    {
        $form  = $this->createForm(EmailAddressType::class);

        $em = $this->getDoctrine()->getManagerForClass(EmailAddress::class);
        $mailer = $this->get('app.mailer.user.verify_email');

        $form->handleRequest($request);

        if ($form->isValid()) {
            $addr = $form->getData();
            $addr->setVerified(false);
            $addr->setUser($this->getUser());
            if(!$this->getUser()->getPrimaryEmailAddress())
                $addr->setPrimary(true);
            $em->persist($addr);
            $em->flush();


            if($addr->getId()) {
                if($mailer->sendMessage($addr->getEmail(), $addr)) {
                    $this->getFlash()->success('A verification email has been sent to your email address. Please click the link to verify your email address.');
                } else {
                    $this->getFlash()->error('We are having some troubles sending you a verification mail. Please try again later.');
                }
            }
        } else {
            $errString = 'Problems with email address '.$form->get('email')->getData().'.';
            foreach ($form->getErrors(true) as $e) {
                $errString.="\n".$e->getMessage();
            }
            $this->getFlash()->error($errString);
        }

        return $this->redirectToProfile();
    }

    /**
     * @View
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->getUser();
        switch ($user->getPasswordEnabled()) {
            default:
            case 0:
                $this->getFlash()->error('Password authentication is disabled for your account');

                return $this->redirectToProfile();
                break;
            case 1:
                $form = $this->createForm(ChangePasswordType::class);
                break;
            case 2:
                $form = $this->createForm(AddPasswordType::class);
                break;
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $user->setPassword(
                $this->get('security.encoder_factory')
                    ->getEncoder(User::class)
                    ->encodePassword($form->get('password')->getData(), null)
            );
            $user->setPasswordEnabled(1);
            $this->getDoctrine()->getManagerForClass(User::class)->flush();
            $this->getFlash()->success('Password has been changed successfully');

            return $this->redirectToProfile();
        }

        return $form;
    }

    public function postGroupAction(Request $request)
    {
        $form = $this->createForm(AddGroupType::class);

        $form->handleRequest($request);

        if($form->isValid()) {
            $group = $form->get('group')->getData();
            /* @var $group Group */
            if(!$group)
                throw $this->createNotFoundException('This group does not exist');
            if(!$group->isUserJoinable())
                throw $this->createAccessDeniedException('This group is not user-joinable');
            $user = $this->getUser();
            /* @var $user User */
            if($user->getGroups()->contains($group)) {
                $this->getFlash()->alert('You are already a member of '.$group->getDisplayName().'.');
                return $this->redirectToProfile();
            }
            $user->addGroup($group);
            $this->getDoctrine()->getManagerForClass(User::class)->flush();
            $this->getFlash()->success('You joined the group '.$group->getDisplayName().'.');
        } else {
            $errString = 'Problems while adding group.';
            foreach ($form->getErrors(true) as $e) {
                $errString.="\n".$e->getMessage();
            }
            $this->getFlash()->error($errString);
        }

        return $this->redirectToProfile();
    }

    /**
     * @View
     * Internal action, not exposed in a route
     */
    public function removeGroupAction($groupId)
    {
        return $this->createForm(DeleteGroupType::class, array('id'=>$groupId));
    }

    public function deleteGroupAction(Request $request)
    {
        $form = $this->createForm(DeleteGroupType::class);

        $form->handleRequest($request);

        if($form->isValid()) {
            $groupId = $form->get('id')->getData();
            $group = $this->getDoctrine()->getRepository(Group::class)->find($groupId);
            /* @var $group Group */
            if($group === null)
                throw $this->createNotFoundException('This group does not exist.');
            if(!$group->isUserLeaveable())
                throw $this->createAccessDeniedException('This group is not user-leaveable.');

            $user = $this->getUser();
            /* @var $user User */
            $user->removeGroup($group);
            $this->getDoctrine()->getManagerForClass(User::class)->flush();
            $this->getFlash()->success('You left the group '.$group->getDisplayName().'.');
        } else {
            $this->getFlash()->error('Cannot leave this group.');
        }

        return $this->redirectToProfile();
    }


    private function redirectToProfile()
    {
        return $this->redirect($this->generateUrl('user_profile'));
    }

    /**
     *
     * @return FlashMessage
     */
    private function getFlash()
    {
        return $this->get('braincrafted_bootstrap.flash');
    }
}
