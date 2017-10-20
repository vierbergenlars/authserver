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

namespace App\Handler;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var EntityManager
     */
    private $manager;

    public function __construct(EntityManager $manager)
    {
        $this->manager = $manager;
    }

    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!($user instanceof User))
            return;
        $this->manager->beginTransaction();
        $this->manager->getRepository('AppBundle:OAuth\\RefreshToken')
                ->createQueryBuilder('t')
                ->delete()
                ->where('t.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->execute();
        $this->manager->getRepository('AppBundle:OAuth\\AccessToken')
                ->createQueryBuilder('t')
                ->delete()
                ->where('t.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->execute();
        $this->manager->commit();
    }
}
