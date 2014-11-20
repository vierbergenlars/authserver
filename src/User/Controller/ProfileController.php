<?php

namespace User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use User\Form\DeleteAuthorizedAppType;
use App\Entity\User;
use User\Form\ChangePasswordType;
use User\Form\EditEmailAddressType;
use App\Entity\EmailAddress;

class ProfileController extends Controller
{
    /**
     * @Template
     */
    public function indexAction()
    {
        return $this->getUser();
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
                $this->getDoctrine()->getRepository('AppBundle:User')->update($user);
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
                        $this->get('app.mailer')
                            ->sendMessage(
                                'AppBundle:Mail:verify_email.mail.twig',
                                array('data'=>$addr),
                                $addr->getEmail()
                            );

                        $flash->success('A new confirmation email has been sent');
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

    /**
     * @Template
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->getUser();
        $form = $this->createForm(new ChangePasswordType());

        $form->handleRequest($request);

        if($form->isValid()) {
            $user->setPassword($form->get('password')->getData());
            $this->getDoctrine()->getRepository('AppBundle:User')->update($user);
            $this->get('braincrafted_bootstrap.flash')->success('Password has been changed successfully');

            return $this->redirectToProfile();
        }

        return $form;
    }

    private function redirectToProfile()
    {
        return $this->redirect($this->generateUrl('user_profile'));
    }
}
