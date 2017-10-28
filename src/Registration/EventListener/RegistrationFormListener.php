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

use App\Entity\User;
use Registration\RegistrationEvents;
use Registration\Event\RegistrationFormEvent;
use Registration\Form\UserType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;

class RegistrationFormListener implements EventSubscriberInterface
{

    /**
     *
     * @var FormFactoryInterface
     */
    private $formFactory;

    public static function getSubscribedEvents()
    {
        return [
            RegistrationEvents::BUILD_FORM => [
                [
                    'onBuildForm',
                    100
                ],
                [
                    'onBuildFormAddData',
                    50
                ]
            ]
        ];
    }

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function onBuildForm(RegistrationFormEvent $event)
    {
        $event->setFormBuilder($this->formFactory->createBuilder(UserType::class));
    }

    public function onBuildFormAddData(RegistrationFormEvent $event)
    {
        if (!$event->getFormBuilder()->getData()) {
            $user = new User();
            $user->setPasswordEnabled(1);
            $event->getFormBuilder()->setData($user);
        }
    }
}
