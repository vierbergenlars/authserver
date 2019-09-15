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

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Templating\TemplateReferenceInterface;
use App\Event\TemplateEventInterface;
use App\Event\TemplateEventTrait;

class DisplayListEvent extends Event implements TemplateEventInterface
{
    use TemplateEventTrait;

    private $class;

    private $entities;

    private $fields = [];

    public function __construct($class, \Traversable $entities)
    {
        $this->class = $class;
        $this->entities = $entities;
    }

    /**
     * The type of entity that this event applies to
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * The items that will be shown on the page
     *
     * @return \Iterator
     */
    public function getItems()
    {
        return new \IteratorIterator($this->entities);
    }

    /**
     * Add a new column to the results table
     *
     * @param string $title
     *            The title of the column
     * @param TemplateReferenceInterface $template
     *            Template to use for the column
     * @param array $extraData
     *            Additional data to render the column
     * @return \Admin\Event\DisplayListEvent
     */
    public function addColumn($title, TemplateReferenceInterface $template, array $extraData = [])
    {
        $this->addTemplate($template, [
            'title' => $title
        ] + $extraData);
        return $this;
    }

    /**
     *
     * @internal
     * @ignore
     * @return array
     */
    public function getColumnHeadings()
    {
        $titles = [];
        foreach ($this->getTemplates() as $template) {
            $templateData = $this->getTemplateData($template);
            if (isset($templateData['title'])) {
                $titles[] = $templateData['title'];
            } else {
                $titles[] = $template->getLogicalName();
            }
        }
        return $titles;
    }
}