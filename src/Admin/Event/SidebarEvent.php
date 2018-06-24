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
namespace Admin\Event;

use App\Event\TemplateEvent;

class SidebarEvent extends TemplateEvent
{

    private $class;

    /**
     *
     * @param string $class
     * @param object|null $entity
     */
    public function __construct($class, $entity = null)
    {
        parent::__construct($entity);
        if ($entity && !is_subclass_of($entity, $class, false)) {
            throw new \LogicException('Can not create an event with an entity that does not match the requested class.');
        }
        $this->class = $class;
    }

    /**
     * Get the classname of the entity for which the batch actions are generated
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}