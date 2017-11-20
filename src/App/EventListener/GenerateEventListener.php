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

class GenerateEventListener implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            AppEvents::GENERATE_HTACCESS => [
                [
                    'generateHeader',
                    260
                ],
                [
                    'generateRewrite',
                    0
                ],
                [
                    'generateHttps',
                    100
                ]
            ],
            AppEvents::GENERATE_MAINTENANCE => [
                'generateMaintenance'
            ]
        ];
    }

    public function generateHeader(TemplateEvent $event)
    {
        $event->addTemplate(new TemplateReference('AppBundle', 'Generate', 'header', 'htaccess', 'twig'));
    }

    public function generateRewrite(TemplateEvent $event)
    {
        $event->addTemplate(new TemplateReference('AppBundle', 'Generate', 'rewrite', 'htaccess', 'twig'));
    }

    public function generateHttps(TemplateEvent $event)
    {
        $event->addTemplate(new TemplateReference('AppBundle', 'Generate', 'https', 'htaccess', 'twig'));
    }

    public function generateMaintenance(TemplateEvent $event)
    {
        $event->addTemplate(new TemplateReference('AppBundle', 'Generate', 'maintenance', 'html', 'twig'));
    }
}
