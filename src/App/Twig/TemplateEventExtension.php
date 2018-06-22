<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;
use App\Event\TemplateEvent;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
            new TwigFunction('event_send', [
                $this,
                'sendTemplateEvent'
            ], [
                'needs_environment' => true,
                'is_safe' => [
                    'html'
                ]
            ])
        ];
    }

    public function renderTemplateEvent(Environment $environment, TemplateEvent $event)
    {
        return $environment->load(new TemplateReference('AppBundle', '', 'templateEvent', 'html', 'twig'))->render([
            'event' => $event
        ]);
    }

    public function sendTemplateEvent(Environment $environment, $eventName, TemplateEvent $event)
    {
        if (defined($eventName)) {
            $eventName = constant($eventName);
        }
        $this->eventDispatcher->dispatch($eventName, $event);
        return $this->renderTemplateEvent($environment, $event);
    }
}