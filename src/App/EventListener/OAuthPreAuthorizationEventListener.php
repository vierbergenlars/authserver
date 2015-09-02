<?php

namespace App\EventListener;

use App\Entity\OAuth\UserAuthorization;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FOS\OAuthServerBundle\Event\OAuthEvent;
use App\Entity\OAuth\Client;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
        $scopes = $event->getScopes()?explode(' ', $event->getScopes()):array();
        if (($client = $event->getClient())&&$client instanceof Client) {
            if ($client->isPreApproved()&&$this->matchesScope($scopes, $client->getPreApprovedScopes())) {
                $event->setAuthorizedClient(true);
            }
            if (($user = $event->getUser())&&$user instanceof User) {
                $authorization = $this->getAuthorization($client, $user);
                if($authorization&&$this->matchesScope($scopes, $authorization->getScopes())) {
                    $event->setAuthorizedClient(true);
                }
                if(!$this->matchesGroupRestriction($client, $user))
                    $event->setAuthorizedClient(false);
            }
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        if ($event->isAuthorizedClient()) {
            if(($client = $event->getClient())&&$client instanceof Client&&
                ($user = $event->getUser())&&$user instanceof User) {
                if(!$this->matchesGroupRestriction($client, $user)) {
                    throw new UnauthorizedHttpException('User is not member of the required group to use this client.');
                } else {
                    $authorization = $this->getAuthorization($client, $user);
                    if ($authorization === null)
                        $authorization = new UserAuthorization($client, $user);
                    $authorization->setScopes(explode(' ', $event->getScopes()));
                    $this->em->persist($authorization);
                    $this->em->flush($authorization);
                }
            }
        }
    }

    private function matchesGroupRestriction(Client $client, User $user)
    {
        return $client->getGroupRestriction() === null || in_array($client->getGroupRestriction(), $user->getGroupsRecursive(), true);
    }

    private function matchesScope($scopes, $restrictions)
    {
        return count(array_diff($scopes, $restrictions)) == 0;
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
