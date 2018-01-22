<?php
namespace OAuthBundle;

use App\Plugin\PluginEvents;
use App\Plugin\Event\ContainerConfigEvent;
use OAuthBundle\DependencyInjection\Compiler\OAuthCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OAuthBundle extends Bundle implements EventSubscriberInterface
{

    /**
     *
     * {@inheritdoc}
     * @see \Symfony\Component\HttpKernel\Bundle\Bundle::build()
     */
    public function build(\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        $container->addCompilerPass(new OAuthCompilerPass());
    }

    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::CONTAINER_CONFIG => [
                'loadFirewallConfig',
                -10
            ]
        ];
    }

    public function loadFirewallConfig(ContainerConfigEvent $event)
    {
        $event->getConfigManipulator('[security][firewalls][api]')->appendConfig([
            'simple_preauth' => [
                'authenticator' => 'app.oauth.authenticator'
            ]
        ]);

        $event->getConfigManipulator('[security][role_hierarchy]')->appendConfig([
            'SCOPE_PROFILE' => [
                'SCOPE_PROFILE:USERNAME',
                'SCOPE_PROFILE:REALNAME',
                'SCOPE_PROFILE:GROUPS'
            ],
            'SCOPE_EMAIL' => [
                'SCOPE_PROFILE:EMAIL'
            ],
            'SCOPE_PROFILE:REALNAME' => [
                'SCOPE_PROFILE:USERNAME'
            ],
            'SCOPE_*' => [
                'SCOPE_OPENID',
                'SCOPE_PROFILE',
                'SCOPE_EMAIL',
                'SCOPE_PROFILE:USERNAME',
                'SCOPE_PROFILE:REALNAME',
                'SCOPE_PROFILE:GROUPS',
                'SCOPE_PROFILE:EMAIL',
                'SCOPE_GROUP:JOIN',
                'SCOPE_GROUP:LEAVE',
                'SCOPE_PROPERTY:READ',
                'SCOPE_PROPERTY:WRITE'
            ]
        ]);
    }
}
