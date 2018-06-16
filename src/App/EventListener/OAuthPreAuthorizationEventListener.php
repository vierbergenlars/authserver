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

use App\Entity\OAuth\UserAuthorization;
use Doctrine\ORM\EntityManagerInterface;
use FOS\OAuthServerBundle\Form\Handler\AuthorizeFormHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use App\Entity\OAuth\Client;
use App\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OAuthPreAuthorizationEventListener implements EventSubscriberInterface
{
    private $em;

    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var FormInterface
     */
    private $authorizeForm;
    /**
     * @var AuthorizeFormHandler
     */
    private $authorizeFormHandler;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, FormInterface $authorizeForm, AuthorizeFormHandler $authorizeFormHandler)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->authorizeForm = $authorizeForm;
        $this->authorizeFormHandler = $authorizeFormHandler;
    }

    public static function getSubscribedEvents()
    {
        return array(
            OAuthEvent::PRE_AUTHORIZATION_PROCESS => 'onPreAuthorizationProcess',
            OAuthEvent::POST_AUTHORIZATION_PROCESS => 'onPostAuthorizationProcess',
        );
    }

    private static function explodeScope($scope)
    {
        if ($scope === '') {
            return [];
        }
        return explode(' ', $scope);
    }

    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        $scopes = self::explodeScope($this->requestStack->getMasterRequest()->query->get('scope', ''));
        $client = $event->getClient();
        $user = $event->getUser();
        if(!($client instanceof Client) || !($user instanceof User))
            throw new \UnexpectedValueException('Invalid type of OAuth Client or User');
        /* @var $client Client */
        /* @var $user User */
        if(!$this->matchesScope($scopes, $client->getMaxScopes()))
            throw new BadRequestHttpException('Client requested scopes outside its allowed scope.');

        if(!$this->matchesGroupRestriction($client, $user)) {
            $event->setAuthorizedClient(false);
            return;
        }

        if ($client->isPreApproved()&&$this->matchesScope($scopes, $client->getPreApprovedScopes())) {
            $event->setAuthorizedClient(true);
            return;
        }

        $authorization = $this->getAuthorization($client, $user);
        if($authorization&&$this->matchesScope($scopes, $authorization->getScopes()))
            $event->setAuthorizedClient(true);
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        $scopes = self::explodeScope($this->authorizeFormHandler->getScope());
        $client = $event->getClient();
        $user = $event->getUser();
        if(!($client instanceof Client) || !($user instanceof User))
            throw new \UnexpectedValueException('Invalid type of OAuth Client or User');
        /* @var $client Client */
        /* @var $user User */
        if(!$this->matchesScope($scopes, $client->getMaxScopes()))
            throw new BadRequestHttpException('Client requested scopes outside its allowed scope.');

        if(!$this->matchesGroupRestriction($client, $user))
            throw new UnauthorizedHttpException('User is not member of the required group to use this client.');

        if(!$event->isAuthorizedClient())
            return;

        $authorization = $this->getAuthorization($client, $user);
        if ($authorization === null)
            $authorization = new UserAuthorization($client, $user);
        $authorization->setScopes($scopes);
        $this->em->persist($authorization);
        $this->em->flush($authorization);
    }

    private function matchesGroupRestriction(Client $client, User $user)
    {
        return $client->getGroupRestriction() === null || in_array($client->getGroupRestriction(), $user->getGroupsRecursive(), true);
    }

    private function matchesScope($scopes, $restrictions)
    {
        return count(array_diff($scopes, $restrictions)) == 0;
    }

    /**
     * @param Client $client
     * @param User   $user
     *
     * @return UserAuthorization
     */
    private function getAuthorization(Client $client, User $user)
    {
        return $this->em->getRepository('AppBundle:OAuth\UserAuthorization')
            ->findOneBy(array(
                'user' => $user,
                'client' => $client,
            ));
    }
}
