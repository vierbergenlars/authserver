<?php

namespace App\EventListener;

use App\Entity\OAuth\UserAuthorization;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use App\Entity\OAuth\Client;
use App\Entity\User;
use vierbergenlars\Bundle\RadRestBundle\Manager\ResourceManagerInterface;

class OAuthPreAuthorizationEventListener implements EventSubscriberInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
        $scopes = explode(' ', $event->getScopes()?:'');
        if (($client = $event->getClient())&&$client instanceof Client) {
            if ($client->isPreApproved()&&count(array_diff($scopes, $client->getPreApprovedScopes())) == 0) {
                $event->setAuthorizedClient(true);
            }
        }
        if (($user = $event->getUser())&&$user instanceof User) {
            $authorization = $this->getAuthorization($client, $user);
            if($authorization&&
                /* Checks if no extra scopes where requested since the authorization */
                count(array_diff($scopes, $authorization->getScopes())) == 0) {
                $event->setAuthorizedClient(true);
            }
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        if ($event->isAuthorizedClient()) {
            if(($client = $event->getClient())&&$client instanceof Client&&
                ($user = $event->getUser())&&$user instanceof User) {
                $authorization = $this->getAuthorization($client, $user);
                if($authorization === null)
                    $authorization = new UserAuthorization($client, $user);
                $authorization->setScopes(explode(' ', $event->getScopes()));
                $this->em->persist($authorization);
                $this->em->flush($authorization);
            }
        }
    }

    /**
     * @param Client $client
     * @param User   $user
     *
     * @return UserAuthorization
     */
    private function getAuthorization(Client $client, User $user)
    {
        return $this->em->getRepository('AppBundle:OAuth\UserAuthorization')
            ->findOneBy(array(
                'user' => $user,
                'client' => $client,
            ));
    }
}
