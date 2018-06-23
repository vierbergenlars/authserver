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

namespace User\EventListener;


use App\Entity\Group;
use App\Entity\User;
use App\Event\TemplateEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use User\Form\EmailAddressType;
use User\UserEvents;
use User\Form\AddGroupType;

class ProfileTemplateListener implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    public function __construct(ManagerRegistry $registry, FormFactoryInterface $formFactory)
    {
        $this->registry = $registry;
        $this->formFactory = $formFactory;
    }

    public static function getSubscribedEvents()
    {
        return [
            UserEvents::USER_PROFILE_VIEW => [
                ['addProfileTop', 30],
                ['addBasicInfo', 20],
                ['addPassword', 10],
                ['addEmail', 0],
                ['addGroups', -10],
                ['addRole', -20],
                ['addProfileBottom', -30]
            ],
        ];
    }

    private function getTemplateReference($template)
    {
        return new TemplateReference('UserBundle', 'Profile', 'index/'.$template, 'html', 'twig');
    }

    public function addProfileTop(TemplateEvent $event)
    {
        $event->addTemplate($this->getTemplateReference('profile_top'));
    }

    public function addBasicInfo(TemplateEvent $event)
    {
        $event->addTemplate($this->getTemplateReference('basic_info'), [
            'displayName' => $event->getSubject()->getDisplayName(),
            'username' => $event->getSubject()->getUsername(),
        ]);
    }

    public function addPassword(TemplateEvent $event)
    {
        $user = $event->getSubject();
        /* @var $user User */
        if($user->getPasswordEnabled())
            $event->addTemplate($this->getTemplateReference('password'), [
                'passwordEnabled' => $user->getPasswordEnabled(),
            ]);
    }

    public function addEmail(TemplateEvent $event)
    {
        $event->addTemplate($this->getTemplateReference('email'), [
            'emailAddresses' => $event->getSubject()->getEmailAddresses(),
            'form' => $this->formFactory->create(EmailAddressType::class)->createView(),
        ]);
    }

    public function addGroups(TemplateEvent $event)
    {
        $user = $event->getSubject();
        /* @var $user User */
        $hasJoinableGroupsBuilder = $this->registry
            ->getManagerForClass(Group::class)
            ->getRepository(Group::class)
            ->createQueryBuilder('g')
            ->select('COUNT(g)')
            ->where('g.noUsers = false AND g.userJoinable = true');
        if($user->getGroups()->count() > 0)
            $hasJoinableGroupsBuilder->andWhere('g NOT IN(:groups)')
                ->setParameter('groups', $user->getGroups());
        $hasJoinableGroups = $hasJoinableGroupsBuilder->getQuery()
                ->getSingleScalarResult() > 0;
        $event->addTemplate($this->getTemplateReference('groups'), [
            'groups' => $user->getGroups(),
            'form' => $hasJoinableGroups ? $this->formFactory->create(AddGroupType::class)
                ->createView() : null
        ]);
    }

    public function addRole(TemplateEvent $event)
    {
        $user = $event->getSubject();
        /* @var $user User */
        if($user->getRole() !== 'ROLE_USER')
            $event->addTemplate($this->getTemplateReference('role'), [
                'role' => $user->getRole(),
            ]);

    }

    public function addProfileBottom(TemplateEvent $event)
    {
        $event->addTemplate($this->getTemplateReference('profile_bottom'));
    }
}
