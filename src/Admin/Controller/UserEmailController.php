<?php

namespace Admin\Controller;

use App\Entity\EmailAddress;
use App\Entity\User;
use App\Form\EmailAddressType;
use App\Mail\PrimedTwigMailer;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;
use Knp\Component\Pager\Paginator;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Class UserEmailController
 * @package Admin\Controller
 * @Security("has_role('ROLE_SCOPE_R_PROFILE_EMAIL') and has_role('ROLE_API')")
 */
class UserEmailController extends Controller implements ClassResourceInterface
{
    private function getResourceManager()
    {
        return $this->get('app.admin.user.repo');
    }

    private function paginate(Request $request, $resource)
    {
        $page = (int)$request->query->get('page', 1);
        $size = (int)$request->query->get('per_page', 10);
        if($page <= 0)
            throw new BadRequestHttpException('The page parameter should be a positive number.');
        if($size <= 0)
            throw new BadRequestHttpException('The per_page parameter should be a positive number.');
        if($size > 1000)
            throw new BadRequestHttpException('The per_page parameter should not exceed 1000.');

        return $this->get('knp_paginator')->paginate($resource, $page, $size);
    }

    /**
     * @ApiDoc
     */
    public function cgetAction(Request $request, User $user)
    {
        $view = View::create();
        $emailAddressesQuery = $this->getDoctrine()
            ->getRepository('AppBundle:EmailAddress')
            ->findByUserQuery($user);
        $view->setData($this->paginate($request, $emailAddressesQuery));

        $view->getSerializationContext()->setGroups(array('list', 'admin_user_email_list'));

        return $view;
    }

    /**
     * @ApiDoc
     */
    public function getAction(User $user, EmailAddress $email)
    {
        if($email->getUser() !== $user)
            throw $this->createNotFoundException();
        $view = View::create($email);
        $view->getSerializationContext()->setGroups(array('object', 'admin_user_email_object'));
        return $view;
    }

    /**
     * @ApiDoc
     */
    public function postAction(Request $request, User $user)
    {
        $email = new EmailAddress();
        $user->addEmailAddress($email);
        $form = $this->createForm(new EmailAddressType(), $email);
        $form->submit(array('email'=>$request->getContent()));
        if(!$form->isValid())
            return $form->get('email');
        $this->getResourceManager()->update($user);
        return View::createRouteRedirect('admin_user_email_get_user_email', array('user' => $user->getId(), 'email' => $email->getId()), Codes::HTTP_CREATED);
    }

    /**
     * @ApiDoc
     */
    public function deleteAction(User $user, EmailAddress $email)
    {
        if($email->getUser() !== $user)
            throw $this->createNotFoundException();
        if($email->isPrimary())
            throw new ConflictHttpException('The primary email address cannot be deleted.');
        $om = $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress');
        $om->remove($email);
        $om->flush();
        return null;
    }

    /**
     * @ApiDoc
     * @Post("users/{user}/emails/{email}/verify")
     */
    public function postVerifyAction(User $user, EmailAddress $email)
    {
        if($email->getUser() !== $user)
            throw $this->createNotFoundException();
        if($email->isVerified())
            throw new ConflictHttpException('This email address has already been verified.');

        $mailer = $this->get('app.mailer.user.verify_email');
        /* @var $mailer PrimedTwigMailer */
        if(!$mailer->sendMessage($email->getEmail(), $email))
            throw new ServiceUnavailableHttpException('Failed to send an email');
        return View::create(null, Codes::HTTP_ACCEPTED);
    }

    /**
     * @ApiDoc
     */
    public function verifyAction(User $user, EmailAddress $email)
    {
        if($email->getUser() !== $user)
            throw $this->createNotFoundException();
        $email->setVerified(true);
        $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress')->flush();
    }

    /**
     * @ApiDoc
     */
    public function primaryAction(User $user, EmailAddress $email)
    {
        if($email->getUser() !== $user)
            throw $this->createNotFoundException();
        if(!$email->isVerified())
            throw new BadRequestHttpException('The email address must be verified before it can be set as the primary email address.');
        $user->getPrimaryEmailAddress()->setPrimary(false);
        $email->setPrimary(true);
        $this->getDoctrine()->getManagerForClass('AppBundle:EmailAddress')->flush();
    }
}