<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserLogListener implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * SwitchUserLogListener constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }

    public function onSwitchUser(SwitchUserEvent $event)
    {
        $logEntry = new LogEntry();
        $logEntry->setAction('security');
        $logEntry->setVersion(0);
        $logEntry->setObjectClass(get_class());
        $logEntry->setData(array('_switch_user' => $event->getRequest()->query->get('_switch_user'), 'target_user'=>$event->getTargetUser()->getId()));
        $logEntry->setUsername($this->tokenStorage->getToken()->getUsername());
        $logEntry->setLoggedAt();
        $this->entityManager->persist($logEntry);
        $this->entityManager->flush();
    }
}
