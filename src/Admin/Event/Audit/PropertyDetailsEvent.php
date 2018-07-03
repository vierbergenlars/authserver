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

use Symfony\Component\Templating\TemplateReferenceInterface;

class PropertyDetailsEvent extends AbstractSingleTemplateEvent implements ClassEventInterface
{

    private $class;

    private $property;

    private $value;

    public function __construct($class, $property, $value)
    {
        $this->class = $class;
        $this->property = $property;
        $this->value = $value;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setTemplate(TemplateReferenceInterface $template, array $extraData = [])
    {
        parent::setTemplate($template, [
            'value' => $this->value
        ] + $extraData);
    }
}