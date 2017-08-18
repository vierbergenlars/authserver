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

namespace ThemingBundle\EventListener;

use App\AppEvents;
use App\Event\MenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use ThemingBundle\Theming\ThemingRoot;

class CustomMenuListener implements EventSubscriberInterface
{
    /**
     * @var ThemingRoot
     */
    private $theming;

    public function __construct(ThemingRoot $theming)
    {
        $this->theming = $theming;
    }

    public static function getSubscribedEvents()
    {
        return [AppEvents::MAIN_MENU => ['onMainMenu', -20]];
    }

    public function onMainMenu(MenuEvent $event)
    {
        if(!$this->theming->getNavbar()->getMenu())
            return;
        $target = $event->getMenu();
        if($target->hasChildren() && !$target->getChild('admin_more'))
            $target->addChild('admin_more', [
                'label' => '.icon-ellipsis-v More'
            ]);
        if($target->getChild('admin_more')) {
            $target = $target->getChild('admin_more');
            if($target->hasChildren())
                $target->addChild('divider_custom_1', [
                    'attributes' => [
                        'divider' => true
                    ]
                ]);
        }
        foreach($this->theming->getNavbar()->getMenu() as $id => $config) {
            $target->addChild($id, $config);
        }
    }
}
