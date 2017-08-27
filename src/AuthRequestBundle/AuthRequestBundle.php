<?php

namespace AuthRequestBundle;

use App\Plugin\Event\ContainerConfigEvent;
use App\Plugin\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;


class AuthRequestBundle extends Bundle implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [PluginEvents::CONTAINER_CONFIG => ['addFirewallConfig', -1]];
    }

    public function addFirewallConfig(ContainerConfigEvent $event)
    {
        $event->getConfigManipulator('[security][firewalls]')->prependConfig([
            'auth_request_basic_api' => [
                'pattern' => '^/api/auth_request/basic',
                'http_basic' => null,
                'stateless' => true,
            ]
        ], 'api');
    }
}
