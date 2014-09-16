<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use App\Entity\OAuth\Client;

class OAuthPreAuthorizationEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'fos_oauth_server.pre_authorization_process' => 'onPreAuthorizationProcess',
        );
    }

    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        if(($client = $event->getClient())&&$client instanceof Client) {
            if($client->isPreApproved()) {
                $event->setAuthorizedClient(true);
            }
        }
    }
}