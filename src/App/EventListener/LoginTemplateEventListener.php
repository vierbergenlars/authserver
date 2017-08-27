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

namespace App\EventListener;


use App\AppEvents;
use App\Event\TemplateEvent;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginTemplateEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            AppEvents::LOGIN_VIEW_BODY => [
                ['onLoginViewBody', 0],
                ['onLoginViewError', 255]
            ],
            AppEvents::LOGIN_VIEW_FOOTER => 'onLoginViewFooter',
        ];
    }

    public function onLoginViewBody(TemplateEvent $event)
    {
        $event->addTemplate(new TemplateReference('AppBundle', 'Security', 'passwordLogin/body', 'html', 'twig'), [
            'last_username' => $event->getArgument('last_username'),
            'event' => $event,
        ]);
    }

    public function onLoginViewError(TemplateEvent $event)
    {
        if($event->getArgument('error'))
            $event->addTemplate(new TemplateReference('AppBundle', 'Security', 'passwordLogin/error', 'html', 'twig'), [
                'last_username' => $event->getArgument('last_username'),
                'error' => $event->getArgument('error'),
                'error_type' => get_class($event->getArgument('error')),
            ]);
    }

    public function onLoginViewFooter(TemplateEvent $event)
    {
        $event->addTemplate(new TemplateReference('AppBundle', 'Security', 'passwordLogin/footer', 'html', 'twig'), [
            'last_username' => $event->getArgument('last_username'),
        ]);
    }
}
