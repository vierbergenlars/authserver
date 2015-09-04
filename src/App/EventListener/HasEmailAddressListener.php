<?php

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserRepository;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HasEmailAddressListener implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserRepository
     */
    private $repo;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var FlashMessage
     */
    private $flash;

    public function __construct(TokenStorageInterface $tokenStorage, UserRepository $repo, UrlGeneratorInterface $urlGenerator, FlashMessage $flash)
    {
        $this->tokenStorage = $tokenStorage;
        $this->repo = $repo;
        $this->urlGenerator = $urlGenerator;
        $this->flash = $flash;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest',
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if(!$event->isMasterRequest())
            return;
        if(!$event->getRequest()->isMethodSafe())
            return;
        if(!($token = $this->tokenStorage->getToken()))
            return;
        if(!($user = $token->getUser()))
            return;
        if(!($user instanceof User))
            return;
        if($user->getPrimaryEmailAddress())
            return;

        switch ($event->getRequest()->attributes->get('_route')) {
            case 'user_profile':
                break;
            default:
                $response = RedirectResponse::create($this->urlGenerator->generate('user_profile'));
                $event->setResponse($response);
        }
        $this->flash->alert('Your profile is missing an email address. Please enter one before continuing');
    }
}
