<?php

namespace Admin\Controller;

use App\Entity\EmailAddress;
use App\Entity\User;
use App\Form\EmailAddressType;
use App\Mail\PrimedTwigMailer;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;

/**
 * @Security("has_role('ROLE_SCOPE_R_PROFILE_EMAIL') and has_role('ROLE_API')")
 * @ParamConverter("user", options={"mapping":{"user":"guid"}})
 * @ParamConverter("email", options={"mapping":{"user":"user", "email":"id"}})
 */
class UserEmailController extends BaseController implements ClassResourceInterface
{
    /**
     * @ApiDoc
     * @View(serializerGroups={"list", "admin_user_email_list"})
     */
    public function cgetAction(Request $request, User $user)
    {
        $emailAddressesQuery = $this->getEntityManager()
            ->getRepository('AppBundle:EmailAddress')
            ->findByUserQuery($user);
        return $this->paginate($emailAddressesQuery, $request);
    }

    /**
     * @ApiDoc
     * @View(serializerGroups={"object", "admin_user_email_object"})
     */
    public function getAction(User $user, EmailAddress $email)
    {
        return $email;
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
        $this->getEntityManager()->flush();
        return $this->routeRedirectView('admin_user_email_get_user_email', array('user' => $user->getGuid(), 'email' => $email->getId()), Codes::HTTP_CREATED);
    }

    /**
     * @ApiDoc
     */
    public function deleteAction(User $user, EmailAddress $email)
    {
        if($email->isPrimary())
            throw new ConflictHttpException('The primary email address cannot be deleted.');
        $this->getEntityManager()->remove($email);
        $this->getEntityManager()->flush();
        return null;
    }

    /**
     * @ApiDoc
     * @Post("users/{user}/emails/{email}/verify")
     */
    public function postVerifyAction(User $user, EmailAddress $email)
    {
        if($email->isVerified())
            throw new ConflictHttpException('This email address has already been verified.');
        $email->setVerified(false);

        $this->getEntityManager()->flush($email);
        $mailer = $this->get('app.mailer.user.verify_email');
        /* @var $mailer PrimedTwigMailer */
        if(!$mailer->sendMessage($email->getEmail(), $email))
            throw new ServiceUnavailableHttpException('Failed to send an email');
        return $this->view(null, Codes::HTTP_ACCEPTED);
    }

    /**
     * @ApiDoc
     */
    public function verifyAction(User $user, EmailAddress $email)
    {
        $email->setVerified(true);
        $this->getEntityManager()->flush();
    }

    /**
     * @ApiDoc
     */
    public function primaryAction(User $user, EmailAddress $email)
    {
        if(!$email->isVerified())
            throw new BadRequestHttpException('The email address must be verified before it can be set as the primary email address.');
        if($user->getPrimaryEmailAddress())
            $user->getPrimaryEmailAddress()->setPrimary(false);
        $email->setPrimary(true);
        $this->getEntityManager()->flush();
    }
}
