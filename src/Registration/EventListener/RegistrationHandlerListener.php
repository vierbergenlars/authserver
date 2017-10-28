<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Registration\EventListener;

use Registration\RegistrationEvents;
use Registration\RegistrationHandler\RegistrationRules;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Mail\PrimedTwigMailer;
use Doctrine\ORM\EntityManagerInterface;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Registration\Event\RegistrationHandleEvent;

class RegistrationHandlerListener implements EventSubscriberInterface
{

    /**
     *
     * @var RegistrationRules
     */
    private $registrationRules;

    /**
     *
     * @var PrimedTwigMailer
     */
    private $mailer;

    /**
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     *
     * @var FlashMessage
     */
    private $flashMessage;

    public static function getSubscribedEvents()
    {
        return [
            RegistrationEvents::HANDLE_FORM => [
                [
                    'handleFormRegistrationRules',
                    100
                ],
                [
                    'handleFormPersist',
                    -10
                ],
                [
                    'handleFormMail',
                    -100
                ],
                [
                    'handleFormFlushData',
                    -200
                ]

            ]
        ];
    }

    public function __construct(RegistrationRules $registrationRules, PrimedTwigMailer $mailer, EntityManagerInterface $em, FlashMessage $flashMessage)
    {
        $this->registrationRules = $registrationRules;
        $this->mailer = $mailer;
        $this->em = $em;
        $this->flashMessage = $flashMessage;
    }

    public function handleFormRegistrationRules(RegistrationHandleEvent $event)
    {
        if ($event->isFailed())
            return;
        $user = $event->getForm()->getData();
        if (!$user)
            return;
        /* @var $user User */
        $emailAddress = $user->getPrimaryEmailAddress()->getEmail();
        $registrationRule = $this->registrationRules->getFirstRuleMatching($emailAddress);
        if (!$registrationRule || !$registrationRule->isSelfRegistration()) {
            $this->flashMessage->error('Self-registration is not allowed with this email address.');
            $event->setFailed();
            return;
        }

        $user->setEnabled($registrationRule->isAutoActivate());
    }

    public function handleFormPersist(RegistrationHandleEvent $event)
    {
        if ($event->isFailed())
            return;
        $user = $event->getForm()->getData();
        if (!$user)
            return;
        /* @var $user User */

        $this->em->persist($user);
        $this->em->flush();
    }

    public function handleFormMail(RegistrationHandleEvent $event)
    {
        if ($event->isFailed())
            return;
        $user = $event->getForm()->getData();
        if (!$user)
            return;
        /* @var $user \App\Entity\User */
        if ($user->getPrimaryEmailAddress()->isVerified()) {
            $this->flashMessage->success('Your account has been registered, and your email address has been verified automatically.');
            return;
        }

        if (!$this->mailer->sendMessage($user->getPrimaryEmailAddress()
            ->getEmail(), $user->getPrimaryEmailAddress())) {
            $this->flashMessage->error('We are having some troubles sending you a verification mail. Please try again later.');
            $event->setFailed();
        } else {
            $this->flashMessage->success('Your account has been registered, please check your mails to confirm your email address.');
        }
    }

    public function handleFormFlushData(RegistrationHandleEvent $event)
    {
        if ($event->isFailed())
            return;
        $user = $event->getForm()->getData();
        if (!$user)
            return;
        /* @var $user User */
        $this->em->flush();
    }
}
