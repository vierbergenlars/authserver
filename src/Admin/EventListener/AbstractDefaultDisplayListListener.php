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

use Admin\Event\DisplayListEvent;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;

abstract class AbstractDefaultDisplayListListener
{

    abstract protected function getClass();

    protected function getControllerName()
    {
        return substr(strrchr($this->getClass(), '\\'), 1);
    }

    private function isApplicable(DisplayListEvent $event)
    {
        return $event->getClass() === $this->getClass();
    }

    private function getTemplateReference($template)
    {
        return new TemplateReference('AdminBundle', $this->getControllerName(), 'cget/' . $template, 'html', 'twig');
    }

    public function __call($name, $arguments)
    {
        if (strpos($name, 'add') !== 0) {
            throw new \BadMethodCallException($name . ' is not a valid method');
        }
        if (!$this->isApplicable($arguments[0]))
            return;
        $displayName = substr($name, 3);
        $templateName = lcfirst($displayName);
        $arguments[0]->addColumn($displayName, $this->getTemplateReference($templateName));
    }
}