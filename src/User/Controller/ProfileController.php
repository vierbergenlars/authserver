<?php

namespace User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use User\Form\DeleteAuthorizedAppType;
use App\Entity\User;
use User\Form\AddPasswordType;
use User\Form\ChangePasswordType;
use User\Form\EditEmailAddressType;
use App\Entity\EmailAddress;
use User\Form\EmailAddressType;

class ProfileController extends Controller
{
    /**
     * @Template
     */
    public function indexAction()
    {
        return array(
            'data'=>$this->getUser(),
            'form' => array(
                'add_email' => $this->createForm(new EmailAddressType())->createView(),
            ),
        );
    }

    /**
     * @Template
     * Internal action, not exposed in a route
     */
    public function removeAuthorizedAppAction($appId)
    {
        return $this->createForm(new DeleteAuthorizedAppType(), array('id'=>$appId));
    }

    public function deleteAuthorizedAppAction(Request $request)
    {
        $form = $this->createForm(new DeleteAuthorizedAppType());

        $form->handleRequest($request);

        if($form->isValid()) {
            $appId = $form->get('id')->getData();
            $client = $this->getDoctrine()
                ->getRepository('AppBundle:OAuth\Client')
                ->find($appId);

            if($client && ($user = $this->getUser()) && $user instanceof User) {
                $user->removeAuthorizedApplication($client);
                $this->getDoctrine()->getManager()->beginTransaction();
                $this->getDoctrine()->getRepository('AppBundle:User')->update($user);
                $this->getDoctrine()->getRepository('AppBundle:OAuth\\RefreshToken')
                        ->createQueryBuilder('t')
                        ->delete()
                        ->where('t.client = :client AND t.user = :user')
                        ->setParameter('client', $client)
                        ->setParameter('user', $user)
                        ->getQuery()
                        ->execute();
                $this->getDoctrine()->getRepository('AppBundle:OAuth\\AccessToken')
                        ->createQueryBuilder('t')
                        ->delete()
                        ->where('t.client = :client AND t.user = :user')
                        ->setParameter('client', $client)
                        ->setParameter('user', $user)
                        ->getQuery()
                        ->execute();
                $this->getDoctrine()->getManager()->commit();
                $this->get('braincrafted_bootstrap.flash')->success('Authorized application has been removed');

                return $this->redirectToProfile();
            }
        }

        $this->get('braincrafted_bootstrap.flash')->error('Error removing authorized application');

        return $this->redirectToProfile();
    }

    /**
     * @Template
     * Internal action, not exposed in a route
     */
    public function editEmailAddressesAction(EmailAddress $addr)
    {
        return array('form'=>$this->createForm(new EditEmailAddressType(), array('id'=>$addr->getId()))->createView(), 'data'=>$addr);
    }

    public function putEmailAddressesAction(Request $request)
    {
        $flash = $this->get('braincrafted_bootstrap.flash');
        $mailer = $this->get('app.mailer.user.verify_email');


        $form  = $this->createForm(new EditEmailAddressType());

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress');

        if($form->isValid()) {
            $id = $form->get('id')->getData();
            $addresses = $this->getUser()->getEmailAddresses();
            $addr = $addresses->filter(function(EmailAddress $e) use($id) {
                return $e->getId() == $id;
            })->first();

            if(!$addr) {
                throw $this->createNotFoundException();
            }

            switch($form->getClickedButton()->getName()) {
                case 'sendConfirmation':
                    if(!$addr->isVerified()) {
                        $addr->setVerified(false);
                        if($mailer->sendMessage($addr->getEmail(), $addr)) {
                            $flash->success('A new confirmation email has been sent');
                        } else {
                            $flash->error('We are having some troubles sending you a verification mail. Please try again later.');
                        }
                    }
                    break;
                case 'setPrimary':
                    if($addr->isVerified()) {
                        $addresses->map(function(EmailAddress $e) {
                            if($e->isPrimary())
                                $e->setPrimary(false);
                        });

                        $addr->setPrimary(true);
                        $flash->success('Primary email address updated');
                    } else {
                        $flash->error('Please verify this email address before setting it as primary email address');
                    }
                    break;
                case 'remove':
                    if(!$addr->isPrimary()) {
                        $em->remove($addr);
                        $flash->success('Email address removed');
                    } else {
                        $flash->error('Your primary email address cannot be removed. You must first set another verified email address as your primary email address.');
                    }
                    break;
                default:
                    // Should never happen
                    $flash->error('Internal error: Unknown button pressed');
            }
            $em->flush($addresses->toArray());
        } else {
            $flash->error('Error modifying email address');
        }

        return $this->redirectToProfile();
    }

    public function postEmailAddressesAction(Request $request)
    {
        $flash = $this->get('braincrafted_bootstrap.flash');

        $form  = $this->createForm(new EmailAddressType());

        $em = $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress');
        $mailer = $this->get('app.mailer.user.verify_email');

        $form->handleRequest($request);

        if($form->isValid()) {
            $addr = $form->getData();
            $addr->setVerified(false);
            $addr->setUser($this->getUser());
            $em->persist($addr);
            $em->flush($addr);

            if($mailer->sendMessage($addr->getEmail(), $addr)) {
                $flash->success('A verification email has been sent to your email address. Please click the link to verify your email address.');
            } else {
                $flash->error('We are having some troubles sending you a verification mail. Please try again later.');
            }
        } else {
            $errString = 'Problems with email address '.$form->get('email')->getData().'.';
            foreach($form->getErrors(true) as $e) {
                $errString.="\n".$e->getMessage();
            }
            $flash->error($errString);
        }

        return $this->redirectToProfile();
    }

    /**
     * @Template
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->getUser();
        switch($user->getPasswordEnabled()) {
            default:
            case 0:
                $this->get('braincrafted_bootstrap.flash')
                    ->error('Password authentication is disabled for your account');
                return $this->redirectToProfile();
                break;
            case 1:
                $form = $this->createForm(new ChangePasswordType());
                break;
            case 2:
                $form = $this->createForm(new AddPasswordType());
                break;
        }

        $form->handleRequest($request);

        if($form->isValid()) {
            $user->setPassword(
                $this->get('security.encoder_factory')
                    ->getEncoder('App\Entity\User')
                    ->encodePassword($form->get('password')->getData(), null)
            );
            $user->setPasswordEnabled(1);
            $this->getDoctrine()->getRepository('AppBundle:User')->update($user);
            $this->get('braincrafted_bootstrap.flash')
                    ->success('Password has been changed successfully');

            return $this->redirectToProfile();
        }

        return $form;
    }

    private function redirectToProfile()
    {
        return $this->redirect($this->generateUrl('user_profile'));
    }
}
