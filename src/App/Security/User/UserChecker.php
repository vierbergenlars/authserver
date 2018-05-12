<?php
namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\AppEvents;
use App\Event\UserCheckerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserChecker implements UserCheckerInterface, EventSubscriberInterface
{

    /**
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     *
     * @var UserCheckerInterface
     */
    private $chainedChecker;

    public function __construct(EventDispatcherInterface $eventDispatcher, UserCheckerInterface $userChecker)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->chainedChecker = $userChecker;
    }

    public function checkPreAuth(UserInterface $user)
    {
        $this->eventDispatcher->dispatch(AppEvents::SECURITY_USER_CHECK_PRE, new UserCheckerEvent($user));
    }

    public function checkPostAuth(UserInterface $user)
    {
        $this->eventDispatcher->dispatch(AppEvents::SECURITY_USER_CHECK_POST, new UserCheckerEvent($user));
    }

    public static function getSubscribedEvents()
    {
        return [
            AppEvents::SECURITY_USER_CHECK_PRE => 'onUserCheckPre',
            AppEvents::SECURITY_USER_CHECK_POST => 'onUserCheckPost'
        ];
    }

    public function onUserCheckPre(UserCheckerEvent $event)
    {
        $this->chainedChecker->checkPreAuth($event->getUser());
    }

    public function onUserCheckPost(UserCheckerEvent $event)
    {
        $this->chainedChecker->checkPostAuth($event->getUser());
    }
}