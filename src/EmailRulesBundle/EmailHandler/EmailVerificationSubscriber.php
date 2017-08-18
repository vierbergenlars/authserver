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

namespace EmailRulesBundle\EmailHandler;

use App\Entity\EmailAddress;
use App\Entity\Group;
use Braincrafted\Bundle\BootstrapBundle\Session\FlashMessage;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EmailVerificationSubscriber implements EventSubscriber
{
    /**
     * @var EmailRules
     */
    private $emailRules;

    /**
     * @var FlashMessage
     */
    private $flashMessage;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * RegistrationHandler constructor.
     *
     * @param EmailRules $emailRules
     * @param FlashMessage $flashMessage
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(EmailRules $emailRules, FlashMessage $flashMessage, TokenStorageInterface $tokenStorage)
    {
        $this->emailRules = $emailRules;
        $this->flashMessage = $flashMessage;
        $this->tokenStorage = $tokenStorage;
    }

    private function getRuleMatching($entity) {
        if(!($entity instanceof EmailAddress))
            return null;
        return $this->emailRules->getFirstRuleMatching($entity->getEmail());
    }

    private function onVerifyMailAddress(LifecycleEventArgs $event)
    {
        if(($rule = $this->getRuleMatching($event->getEntity())) !== null) {
            $emailAddress = $event->getEntity();
            /* @var $emailAddress EmailAddress */
            if(!$emailAddress->isVerified())
                return;
            $user = $emailAddress->getUser();
            $em = $event->getEntityManager();
            $groups = $rule->getGroups();

            $userGroupNames = $user->getGroups()->map(function(Group $group) {
                return $group->getName();
            })->toArray();

            $newGroups = array_diff($groups, $userGroupNames);

            if(count($newGroups) > 0) {
                $groupEntities = $em->getRepository(Group::class)
                    ->createQueryBuilder('g')
                    ->where('g.name IN(:groups)')
                    ->setParameter('groups', $newGroups)
                    ->getQuery()
                    ->getResult();
                foreach($groupEntities as $group)
                    $user->addGroup($group);

                $matchedGroupEntities = count($groupEntities);
                $token = $this->tokenStorage->getToken();
                if(!$token || $token->getUser() === $user) {
                    $alertMessage = 'You have been added to the ';
                } else {
                    $alertMessage = 'User '.$user->getUsername().' has been added to the ';
                }

                switch($matchedGroupEntities) {
                    case 0:
                        break;
                    case 1:
                        $alertMessage .= sprintf('"%s" group.', $groupEntities[0]->getDisplayName());
                        break;
                    default:
                        $lastGroup = array_pop($groupEntities);
                        $alertMessage .= sprintf('following groups: "%s" and "%s".', implode('", "', array_map(function($g) { return $g->getDisplayName(); }, $groupEntities)), $lastGroup->getDisplayName());
                }

                $this->flashMessage->success($alertMessage);
            }

            $user->upgradeRole($rule->getRole());

            $em->flush($user);
        }

    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $this->onVerifyMailAddress($event);
    }

    public function postUpdate(LifecycleEventArgs $event)
    {
        $this->onVerifyMailAddress($event);
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        $uow = $event->getEntityManager()->getUnitOfWork();
        foreach(array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledCollectionUpdates()) as $entity) {
            if($entity instanceof EmailAddress) {
                $rule = $this->emailRules->getFirstRuleMatching($entity->getEmail());

                if($rule && $rule->isReject()) {
                    throw new \LogicException('Rejected email address was not stopped before flush.');
                }
            }
        }
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::postUpdate,
            Events::postPersist,
        ];
    }
}
