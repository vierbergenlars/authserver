<?php

namespace User\Controller;

use App\Entity\EmailAddress;
use App\Entity\Property;
use App\Entity\User;
use App\Entity\UserProperty;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use User\Form\AddPasswordType;
use User\Form\ChangePasswordType;
use User\Form\DeleteAuthorizedAppType;
use User\Form\EditEmailAddressType;
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

        if ($form->isValid()) {
            $appId = $form->get('id')->getData();
            $client = $this->getDoctrine()
                ->getRepository('AppBundle:OAuth\Client')
                ->find($appId);

            if ($client && ($user = $this->getUser()) && $user instanceof User) {
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
                $this->getFlash()->success('Authorized application has been removed');

                return $this->redirectToProfile();
            }
        }

        $this->getFlash()->error('Error removing authorized application');

        return $this->redirectToProfile();
    }

    /**
     * @Template
     * Internal action, not exposed in a route
     */
    public function editEmailAddressesAction(EmailAddress $addr)
    {
        return array('form'=>$this->createForm(new EditEmailAddressType())->createView(), 'data'=>$addr);
    }

    public function putEmailAddressesAction(EmailAddress $addr, Request $request)
    {
        if($addr->getUser() !== $this->getUser())
            throw $this->createNotFoundException();
        $mailer = $this->get('app.mailer.user.verify_email');

        $form  = $this->createForm(new EditEmailAddressType());

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
                                ->getManagerForClass('AppBundle:EmailAddress')
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
                    ->getManagerForClass('AppBundle:EmailAddress')
                    ->flush();
        } else {
            $this->getFlash()->error('Error modifying email address');
        }

        return $this->redirectToProfile();
    }

    public function postEmailAddressesAction(Request $request)
    {
        $form  = $this->createForm(new EmailAddressType());

        $em = $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress');
        $mailer = $this->get('app.mailer.user.verify_email');

        $form->handleRequest($request);

        if ($form->isValid()) {
            $addr = $form->getData();
            $addr->setVerified(false);
            $addr->setUser($this->getUser());
            $em->persist($addr);
            $em->flush($addr);

            if ($mailer->sendMessage($addr->getEmail(), $addr)) {
                $this->getFlash()->success('A verification email has been sent to your email address. Please click the link to verify your email address.');
            } else {
                $this->getFlash()->error('We are having some troubles sending you a verification mail. Please try again later.');
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
     * @Template
     */
    public function editPropertyAction(UserProperty $property)
    {
        return array(
            'form' => $this->createForm(new \User\Form\EditUserPropertyType(), $property)
                        ->createView(),
            'data' => $property
        );
    }

    public function putPropertyAction(Property $property, Request $request)
    {
        if(!$property->isUserEditable())
            throw $this->createNotFoundException();

        $em = $this->getDoctrine()
                ->getManagerForClass('AppBundle:UserProperty');
        $userProperty = $em->getRepository('AppBundle:UserProperty')
                ->findOneBy(array(
                    'user' => $this->getUser(),
                    'property' => $property
                ));

        $form = $this->createForm(new \User\Form\EditUserPropertyType(), $userProperty);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->persist($userProperty);
            $em->flush($userProperty);
            $this->getFlash()->success('Information updated successfully.');
        } else {
            if ($property->isRequired() && !$userProperty->getData()) {
                $this->getFlash()->error('This field should not be left blank. Please try again.');
            } else {
                $this->getFlash()->error('Updating information failed.');
            }
        }

        return $this->redirectToProfile();
    }

    /**
     * @Template
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
                $form = $this->createForm(new ChangePasswordType());
                break;
            case 2:
                $form = $this->createForm(new AddPasswordType());
                break;
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $user->setPassword(
                $this->get('security.encoder_factory')
                    ->getEncoder('App\Entity\User')
                    ->encodePassword($form->get('password')->getData(), null)
            );
            $user->setPasswordEnabled(1);
            $this->getDoctrine()->getRepository('AppBundle:User')->update($user);
            $this->getFlash()->success('Password has been changed successfully');

            return $this->redirectToProfile();
        }

        return $form;
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
