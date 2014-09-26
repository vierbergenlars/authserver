<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use App\Entity\OAuth\Client;
use App\Entity\User;
use vierbergenlars\Bundle\RadRestBundle\Manager\ResourceManagerInterface;

class OAuthPreAuthorizationEventListener implements EventSubscriberInterface
{
    private $resourceManager;

    public function __construct(ResourceManagerInterface $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'fos_oauth_server.pre_authorization_process' => 'onPreAuthorizationProcess',
            'fos_oauth_server.post_authorization_process' => 'onPostAuthorizationProcess',
        );
    }

    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        if(($client = $event->getClient())&&$client instanceof Client) {
            if($client->isPreApproved()) {
                $event->setAuthorizedClient(true);
            }
        }
        if(($user = $event->getUser())&&$user instanceof User) {
            if($user->getAuthorizedApplications()->contains($client)) {
                $event->setAuthorizedClient(true);
            }
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        if($event->isAuthorizedClient()) {
            if(($client = $event->getClient())&&$client instanceof Client&&
                ($user = $event->getUser())&&$user instanceof User) {
                $user->addAuthorizedApplication($client);
                $this->resourceManager->update($user);
            }
        }
    }
}
