<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserRepository;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class CancelPasswordResetOnLoginListener implements EventSubscriberInterface
{
    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var FlashMessage
     */
    private $flash;

    public function __construct(EntityManagerInterface $em, FlashMessage $flash)
    {
        $this->userRepo = $em->getRepository('AppBundle:User');
        $this->flash = $flash;
    }

    public static function getSubscribedEvents()
    {
        return array(
            SecurityEvents::INTERACTIVE_LOGIN => 'onLogin'
        );
    }

    public function onLogin(InteractiveLoginEvent $loginEvent)
    {
        $user = $loginEvent->getAuthenticationToken()->getUser();
        /* @var $user User */
        if($user->getPasswordResetToken() !== null) {
            $this->flash->info('Since you appear to have logged in successfully, we canceled your pending password reset request.');
            $user->clearPasswordResetToken();
            $this->userRepo->update($user);
        }
    }
}