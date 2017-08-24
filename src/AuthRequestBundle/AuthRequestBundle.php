<?php

namespace AuthRequestBundle;

use App\Plugin\BundleExtension\FirewallManipulatorTrait;
use App\Plugin\Event\ContainerConfigEvent;
use App\Plugin\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AuthRequestBundle extends Bundle implements EventSubscriberInterface
{
    use FirewallManipulatorTrait;
    public static function getSubscribedEvents()
    {
        return [PluginEvents::CONTAINER_CONFIG => ['addFirewallConfig', -1]];
    }

    public function addFirewallConfig(ContainerConfigEvent $event)
    {
        $this->addFirewall($event, [
            'auth_request_basic_api' => [
                'pattern' => '^/api/auth_request/basic',
                'http_basic' => null,
                'stateless' => true,
            ]
        ], 'api');
    }
}
