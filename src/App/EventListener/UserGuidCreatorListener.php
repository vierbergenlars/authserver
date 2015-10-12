<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Id\UuidGenerator;

class UserGuidCreatorListener implements EventSubscriber
{

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(Events::prePersist);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        if($args->getEntity() instanceof User&&!$args->getEntity()->getGuid()) {
            $uuidGenerator = new UuidGenerator();
            $uuid = $uuidGenerator->generate($args->getEntityManager(), $args->getEntity());
            $args->getEntity()->setGuid($uuid);
        }
    }
}
