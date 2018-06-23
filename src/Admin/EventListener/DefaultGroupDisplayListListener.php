<?php
namespace Admin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Admin\AdminEvents;
use App\Entity\Group;

class DefaultGroupDisplayListListener extends AbstractDefaultDisplayListListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            AdminEvents::DISPLAY_LIST => [
                [
                    'addTechnicalName',
                    150
                ],
                [
                    'addDisplayName',
                    100
                ],
                [
                    'addFlags',
                    50
                ],

                [
                    'addActions',
                    -100
                ]

            ]
        ];
    }

    protected function getClass()
    {
        return Group::class;
    }
}