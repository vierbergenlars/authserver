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

use App\AppEvents;
use App\Event\MenuEvent;
use Registration\Entity\TemporaryUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

class TemporaryUserListener implements EventSubscriberInterface
{

    /**
     *
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                'onRequest',
                10
            ],
            AppEvents::PROFILE_MENU => [
                'onProfileMenu',
                10
            ]
        ];
    }

    public function __construct(TokenStorageInterface $tokenStorage, UrlGeneratorInterface $urlGenerator, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    private function isTemporaryUserToken(TokenInterface $token = null)
    {
        if (!$token)
            return false;
        return $token->getUser() instanceof TemporaryUser;
    }

    private function isTemporaryUserLogin(Request $request)
    {
        $session = $request->getSession();
        if (!$session)
            return false;
        $security = $session->get('_security_public');
        if (!$security)
            return false;
        $token = unserialize($security);

        return $this->isTemporaryUserToken($token);
    }

    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest())
            return;
        if (strpos($event->getRequest()->getRequestUri(), '/_') === 0)
            return;
        if (!$this->isTemporaryUserLogin($event->getRequest()))
            return;

        $this->logger->debug('Got a temporary user login.');

        $allowedRoutes = [
            'registration_register',
            'logout'
        ];

        if (in_array($event->getRequest()->attributes->get('_route'), $allowedRoutes))
            return;

        $this->logger->info(sprintf('Request route %s is not allowed. Redirect to registration.', $event->getRequest()->attributes->get('_route')), [
            'request_attributes' => $event->getRequest()->attributes
        ]);

        $event->setResponse(RedirectResponse::create($this->urlGenerator->generate('registration_register')));
    }

    public function onProfileMenu(MenuEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if ($this->isTemporaryUserToken($token)) {
            $user = $token->getUser();
            /* @var $user \Registration\Entity\TemporaryUser */
            $event->addChild('logout', [
                'route' => 'logout',
                'label' => '.icon-sign-out Sign out (' . $user->getDisplayName() . ')'
            ]);
        }
    }
}
