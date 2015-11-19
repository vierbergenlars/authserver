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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class CancelPasswordResetOnLoginListener implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var FlashMessage
     */
    private $flash;

    public function __construct(EntityManagerInterface $em, FlashMessage $flash)
    {
        $this->em = $em;
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
        if($user instanceof User && $user->getPasswordResetToken() !== null) {
            $this->flash->info('Since you appear to have logged in successfully, we canceled your pending password reset request.');
            $user->clearPasswordResetToken();
            $this->em->flush();
        }
    }
}
