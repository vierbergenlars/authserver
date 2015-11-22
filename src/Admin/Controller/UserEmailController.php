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

namespace Admin\Controller;

use App\Entity\EmailAddress;
use App\Entity\User;
use App\Form\EmailAddressType;
use App\Mail\PrimedTwigMailer;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
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
     * @View(serializerGroups={"object", "admin_user_email_object"})
     */
    public function getAction(User $user, EmailAddress $email)
    {
        return $email;
    }

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

    public function deleteAction(User $user, EmailAddress $email)
    {
        if($email->isPrimary())
            throw new ConflictHttpException('The primary email address cannot be deleted.');
        $this->getEntityManager()->remove($email);
        $this->getEntityManager()->flush();
        return null;
    }

    /**
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

    public function verifyAction(User $user, EmailAddress $email)
    {
        $email->setVerified(true);
        $this->getEntityManager()->flush();
    }

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
