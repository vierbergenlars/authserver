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
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\VarDumper\VarDumper;

class EmailValidationSubscriber implements EventSubscriber
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
     * RegistrationHandler constructor.
     *
     * @param EmailRules $emailRules
     * @param FlashMessage $flashMessage
     */
    public function __construct(EmailRules $emailRules, FlashMessage $flashMessage)
    {
        $this->emailRules = $emailRules;
        $this->flashMessage = $flashMessage;
    }

    private function getRuleMatching($entity) {
        if(!($entity instanceof EmailAddress))
            return null;
        return $this->emailRules->getFirstRuleMatching($entity->getEmail());
    }

    private function isRejected($email) {
        $rule = $this->emailRules->getFirstRuleMatching($email);
        return $rule && $rule->isReject();
    }

    public function onFlush(OnFlushEventArgs $event) {
        $uow = $event->getEntityManager()->getUnitOfWork();
        foreach($uow->getScheduledEntityInsertions() as $entity) {
            if($entity instanceof EmailAddress) {
                if($this->isRejected($entity->getEmail())) {
                    // Cancel insertion of rejected email address
                    $this->flashMessage->error('This email address has been rejected.');
                    $uow->scheduleForDelete($entity);
                }
            }
            if($entity instanceof LogEntry) {
                if($entity->getObjectClass() !== EmailAddress::class)
                    continue;
                $data = $entity->getData();
                if(isset($data['email']) && $this->isRejected($data['email']))
                    $uow->scheduleForDelete($entity);
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $event)
    {
        if(($rule = $this->getRuleMatching($event->getEntity())) !== null) {
            if($rule->isReject()) {
                $this->flashMessage->error('This email address has been rejected.');
                // If email address is rejected, set all changed fields to their previous values
                foreach($event->getEntityChangeSet() as $field => $_) {
                    $event->setNewValue($field, $event->getOldValue($field));
                }
            }
        }

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

            if(count($groups) > 0) {
                $groupEntities = $em->getRepository(Group::class)
                    ->createQueryBuilder('g')
                    ->where('g.name IN(:groups)')
                    ->setParameter('groups', $groups)
                    ->getQuery()
                    ->getResult();
                foreach($groupEntities as $group)
                    $user->addGroup($group);
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

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::preUpdate,
            Events::postUpdate,
            Events::postPersist,
        ];
    }
}
