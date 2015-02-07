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

class UserPropertiesValidEventListener implements EventSubscriberInterface {

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

    function __construct(TokenStorageInterface $tokenStorage, UserRepository $repo, UrlGeneratorInterface $urlGenerator, FlashMessage $flash) {
        $this->tokenStorage = $tokenStorage;
        $this->repo = $repo;
        $this->urlGenerator = $urlGenerator;
        $this->flash = $flash;
    }

    public static function getSubscribedEvents() {
        return array(
            KernelEvents::REQUEST => 'onKernelRequest',
        );
    }

    public function onKernelRequest(GetResponseEvent $event) {
        if(!$event->isMasterRequest())
            return;
        if(!($token = $this->tokenStorage->getToken()))
            return;
        if(!($user = $token->getUser()))
            return;
        if(!($user instanceof User))
            return;
        if(count($requiredEmptyProperties = $this->repo->getAllEmptyRequiredProperties($user)) == 0)
            return;
        if(!array_filter(
            $token->getRoles(),
            function(\Symfony\Component\Security\Core\Role\RoleInterface $role) {
                return $role instanceof \Symfony\Component\Security\Core\Role\SwitchUserRole;
            }
        )) {
            switch($event->getRequest()->attributes->get('_route')) {
                case 'user_profile':
                case 'user_put_property':
                    break;
                default:
                    $response = RedirectResponse::create($this->urlGenerator->generate('user_profile'));
                    $event->setResponse($response);
            }
        } else {
            $this->flash->info('Automatic redirect to user profile suppressed, because you are impersonating this user.');
        }
        $this->flash->alert(sprintf(
                'Your profile is missing required information. Please fill in "%s".',
                implode('", "', array_map(function(\App\Entity\Property $prop) {
                    return $prop->getName();
                }, $requiredEmptyProperties))
        ));
    }
}
