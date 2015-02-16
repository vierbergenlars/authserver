<?php

namespace Admin\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MimeTypeListener implements EventSubscriberInterface
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        switch ($event->getRequest()->getRequestFormat()) {
            case 'xml':
            case 'json':
                $matches = [];
                if (preg_match('/^admin_(?P<object>[a-z_]+)_(?P<type>[a-z]+)/', $event->getRequest()->attributes->get('_route'), $matches)) {
                    $headers = $event->getResponse()->headers;
                    if ($headers->get('content-type') !== null) {
                        list($mainType,$subType) = explode('/', $headers->get('content-type'), 2);
                        $headers->set('content-type', $mainType.'/vnd.be.vbgn.authserver.api.'.$matches['object'].($matches['type']=='gets'?'.list':'').'+'.$subType);
                    }
                }

                break;
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

}
