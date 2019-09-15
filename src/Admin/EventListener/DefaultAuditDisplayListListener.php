<?php
/*
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2018 Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Admin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Admin\AdminEvents;
use Gedmo\Loggable\Entity\LogEntry;
use Admin\Event\DisplayListEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Admin\Event\Audit\ActionEvent;
use Admin\AuditEvents;

class DefaultAuditDisplayListListener extends AbstractDefaultDisplayListListener implements EventSubscriberInterface
{

    /**
     *
     * @var ActionEvent
     */
    private $cachedActionEvent;

    public static function getSubscribedEvents()
    {
        return [
            AdminEvents::DISPLAY_LIST => [
                [
                    'addTarget',
                    150
                ],
                [
                    'addAction',
                    100
                ],
                [
                    'addUsername',
                    50
                ],
                [
                    'addDate',
                    -50
                ],
                [
                    'addActions',
                    -100
                ]

            ]
        ];
    }

    protected function getControllerName()
    {
        return 'Audit';
    }

    protected function getClass()
    {
        return LogEntry::class;
    }

    public function addAction(DisplayListEvent $event, $eventName, EventDispatcherInterface $eventDispatcher)
    {
        if (!$this->isApplicable($event))
            return;

        if (!$this->cachedActionEvent) {
            $this->cachedActionEvent = new ActionEvent();
            $eventDispatcher->dispatch(AuditEvents::ACTION, $this->cachedActionEvent);
        }

        $event->addColumn('Action', $this->getTemplateReference('action'), [
            'action_event' => $this->cachedActionEvent
        ]);
    }
}