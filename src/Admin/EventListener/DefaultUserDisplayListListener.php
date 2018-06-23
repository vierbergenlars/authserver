<?php
namespace Admin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Admin\AdminEvents;
use App\Entity\User;

class DefaultUserDisplayListListener extends AbstractDefaultDisplayListListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            AdminEvents::DISPLAY_LIST => [
                [
                    'addUsername',
                    150
                ],
                [
                    'addDisplayName',
                    100
                ],
                [
                    'addEmail',
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
        return User::class;
    }
}