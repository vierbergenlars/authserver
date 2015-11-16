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

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserRepository;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, FlashMessage $flash)
    {
        $this->tokenStorage = $tokenStorage;
        $this->repo = $entityManager->getRepository('AppBundle:User');
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
