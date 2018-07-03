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
namespace Admin\Event\Audit;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Templating\TemplateReferenceInterface;

class ActionEvent extends Event
{

    /**
     *
     * @var array
     */
    private $actions = [];

    public function __construct()
    {}

    public function addAction($actionName, TemplateReferenceInterface $template)
    {
        $this->actions[$actionName] = $template;
    }

    public function getAction($actionName)
    {
        return $this->actions[$actionName];
    }
}