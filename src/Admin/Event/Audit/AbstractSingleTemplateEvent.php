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
use App\Event\TemplateEventInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\iterator;

abstract class AbstractSingleTemplateEvent extends Event implements TemplateEventInterface
{

    private $template;

    private $extraData;

    public function setTemplate(TemplateReferenceInterface $template, array $extraData = [])
    {
        if ($this->template) {
            throw new \BadMethodCallException(sprintf('%s::setTemplate() can only be called once.', get_class($this)));
        }
        $this->template = $template;
        $this->extraData = $extraData;
        $this->stopPropagation();
    }

    public function getTemplateData(TemplateReferenceInterface $template)
    {
        if ($this->template !== $template) {
            throw new \DomainException('Can only fetch template data for a template that is present in this event.');
        }
        return $this->extraData;
    }

    public function getTemplates()
    {
        if (!$this->template) {
            return new \EmptyIterator();
        }
        return new \IteratorIterator(new \ArrayIterator([
            $this->template
        ]));
    }
}