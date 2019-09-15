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
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\TemplateEventInterface;
use Symfony\Component\EventDispatcher\Event;

class TemplateEventExtension extends AbstractExtension
{

    /**
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('event_render', [
                $this,
                'renderTemplateEvent'
            ], [
                'needs_environment' => true,
                'is_safe' => [
                    'html'
                ]
            ]),
            new TwigFunction('event_dispatch', [
                $this,
                'dispatchEvent'
            ], [
                'is_variadic' => true
            ]),
            new TwigFunction('event_create', [
                $this,
                'createEvent'
            ], [
                'is_variadic' => true
            ]),
            new TwigFunction('event_send', [
                $this,
                'sendTemplateEvent'
            ], [
                'is_variadic' => true,
                'needs_environment' => true,
                'is_safe' => [
                    'html'
                ]
            ])
        ];
    }

    /**
     * Renders all templates that have been added to a template event
     *
     * @param Environment $environment
     * @param TemplateEventInterface $event
     * @return string
     */
    public function renderTemplateEvent(Environment $environment, TemplateEventInterface $event)
    {
        return $environment->load(new TemplateReference('AppBundle', '', 'templateEvent', 'html', 'twig'))->render([
            'event' => $event
        ]);
    }

    private function doDispatchEvent($eventName, $event, array $arguments = [])
    {
        if (is_string($event)) {
            $event = $this->createEvent($event, $arguments);
        } else if (count($arguments) != 0) {
            throw new \BadMethodCallException('event_dispatch: Only 2 parameters may be passed when an event instance is used.');
        }
        $eventName = constant($eventName);
        return $this->eventDispatcher->dispatch($eventName, $event);
    }

    /**
     * Dispatches an event
     *
     * @param string $eventName
     *            The name of the constant of the event to dispatch
     * @param Event $event
     *            The event to dispatch.
     * @internal If it is a string, {@link createEvent()} is used to create a new event
     * @internal @param array $arguments
     *           If $event is a string, constructor parameters to pass when creating an event
     */
    public function dispatchEvent($eventName, $event, array $arguments = [])
    {
        $this->doDispatchEvent($eventName, $event, $arguments);
    }

    /**
     * Dispatches and renders an event
     *
     * @param Environment $environment
     * @param string $eventName
     *            The name of the constant of the event to dispatch {@see dispatchEvent()}
     * @param string|Event $event
     *            The event to dispatch. If it is a string, {@link createEvent()} is used to create a new event
     * @param array $arguments
     *            If $event is a string, constructor parameters to pass when creating an event
     * @return string
     */
    public function sendTemplateEvent(Environment $environment, $eventName, $event, array $arguments = [])
    {
        $event = $this->doDispatchEvent($eventName, $event, $arguments);
        return $this->renderTemplateEvent($environment, $event);
    }

    /**
     * Creates a new event
     *
     * @param string $eventClass
     *            The classname of the event to create
     * @param array $arguments
     *            Constructor parameters to create the event with
     * @return Event
     */
    public function createEvent($eventClass, array $arguments = [])
    {
        if (!class_exists($eventClass, true)) {
            throw new \InvalidArgumentException('event_create: ' . $eventClass . ' is not an existing class');
        }
        if (!is_subclass_of($eventClass, Event::class, true)) {
            throw new \DomainException('event_create: Only subclasses of Event can be created by this function');
        }
        $refl = new \ReflectionClass($eventClass);
        return $refl->newInstanceArgs($arguments);
    }
}