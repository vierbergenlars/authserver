<?php

namespace AuthRequestBundle;

use App\Plugin\Event\ContainerConfigEvent;
use App\Plugin\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AuthRequestBundle extends Bundle implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [PluginEvents::CONTAINER_CONFIG => ['addFirewallConfig', -1]];
    }

    public function addFirewallConfig(ContainerConfigEvent $event)
    {
        $config = $event->getConfig();
        $pa = PropertyAccess::createPropertyAccessor();
        $firewall = $pa->getValue($config, '[security][firewall]');
        if(!$firewall)
            $firewall = [];

        $firewallOrder = array_keys($firewall);

        $firewall['auth_request_basic_api'] = [
            'name' => 'auth_request_basic_api',
            'pattern' => '^/api/auth_request/basic',
            'http_basic' => null,
            'stateless' => true,
        ];

        array_unshift($firewallOrder, 'auth_request_basic_api');

        uksort($firewall, function($a, $b) use($firewallOrder) {
            return array_search($a, $firewallOrder, true) - array_search($b, $firewallOrder, true);
        });

        $pa->setValue($config, '[security][firewall]', $firewall);

        $event->setConfig($config);
    }
}
