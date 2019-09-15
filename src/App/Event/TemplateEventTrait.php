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


use Symfony\Component\Templating\TemplateReferenceInterface;

trait TemplateEventTrait
{
    /**
     * @var \SplObjectStorage
     */
    private $templates;

    /**
     * @var array
     */
    private $globalData = [];

    private function _getTemplates()
    {
        if(!$this->templates)
            $this->templates = new \SplObjectStorage();
        return $this->templates;
    }

    public function addTemplate(TemplateReferenceInterface $template, array $extraData = [])
    {
        $this->_getTemplates()->attach($template, $extraData);
        return $this;
    }

    public function getTemplates()
    {
        return new \IteratorIterator($this->_getTemplates());
    }

    public function getTemplateData(TemplateReferenceInterface $template)
    {
        return $this->_getTemplates()[$template] + $this->globalData;
    }

    public function count()
    {
        return $this->_getTemplates()->count();
    }

    public function setGlobalData($globalData)
    {
        $this->globalData = $globalData;
    }
}
