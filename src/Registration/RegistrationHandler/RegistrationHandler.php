<?php


namespace Registration\RegistrationHandler;

use App\Entity\EmailAddress;
use App\Entity\Group;
use App\Entity\User;
use App\Mail\PrimedTwigMailer;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Doctrine\ORM\EntityManagerInterface;
use Registration\Form\UserType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class RegistrationHandler
{
    /**
     * @var RegistrationRules
     */
    private $registrationRules;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var FormFactory
     */
    private $formFactory;
    /**
     * @var PrimedTwigMailer
     */
    private $mailer;
    /**
     * @var FlashMessage
     */
    private $flashMessage;

    /**
     * RegistrationHandler constructor.
     *
     * @param RegistrationRules $registrationRules
     * @param PrimedTwigMailer $mailer
     * @param FormFactory $formFactory
     * @param EntityManagerInterface $em
     * @param FlashMessage $flashMessage
     */
    public function __construct(RegistrationRules $registrationRules, PrimedTwigMailer $mailer, FormFactory $formFactory, EntityManagerInterface $em, FlashMessage $flashMessage)
    {
        $this->registrationRules = $registrationRules;
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->mailer = $mailer;
        $this->flashMessage = $flashMessage;
    }

    /**
     * @return FormInterface
     */
    public function getRegistrationForm()
    {
        $user = new User();
        $user->setPasswordEnabled(1);
        return $this->formFactory->create(UserType::class, $user);
    }

    public function handleRequest(Request $request)
    {
        $form = $this->getRegistrationForm();
        $form->handleRequest($request);
        if($form->isValid()) {
            $user = $form->getData();
            /* @var $user User */
            $emailAddress = $user->getPrimaryEmailAddress()->getEmail();
            $registrationRule = $this->registrationRules->getFirstRuleMatching($emailAddress);
            if(!$registrationRule||!$registrationRule->isSelfRegistration())
                throw new \LogicException('Self-registration checking should already have been applied on the form');

            $user->setEnabled($registrationRule->isAutoActivate());

            $this->em->persist($user);
            $this->em->flush();

            if(!$this->mailer->sendMessage($user->getPrimaryEmailAddress()->getEmail(), $user->getPrimaryEmailAddress())) {
                $this->flashMessage->error('We are having some troubles sending you a verification mail. Please try again later.');
                return $form;
            } else {
                $this->em->flush();
                $this->flashMessage->success('Your account has been registered, please check your mails to confirm your email address.');
                return null;
            }
        }
        return $form;
    }
    
}
