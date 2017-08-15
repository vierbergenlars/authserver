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

namespace App\Event;


use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\Event;

class MenuEvent extends Event
{
    /**
     * @var ItemInterface
     */
    private $menu;

    public function __construct(ItemInterface $menu)
    {

        $this->menu = $menu;
    }

    /**
     * @return ItemInterface
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * Add a child menu item to this menu
     *
     * Returns the child item
     *
     * @param ItemInterface|string $child   An ItemInterface instance or the name of a new item to create
     * @param array                $options If creating a new item, the options passed to the factory for the item
     *
     * @return ItemInterface
     * @throws \InvalidArgumentException if the item is already in a tree
     */
    public function addChild($child, array $options = [])
    {
        return $this->menu->addChild($child, $options);
    }
}
