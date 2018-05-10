<?php
namespace App\Security\User;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\AppEvents;
use App\Event\UserCheckerEvent;

class UserChecker implements UserCheckerInterface
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
        $this->chainedChecker->checkPreAuth($user);
        $this->eventDispatcher->dispatch(AppEvents::SECURITY_USER_CHECK_PRE, new UserCheckerEvent($user));
    }

    public function checkPostAuth(UserInterface $user)
    {
        $this->chainedChecker->checkPostAuth($user);
        $this->eventDispatcher->dispatch(AppEvents::SECURITY_USER_CHECK_POST, new UserCheckerEvent($user));
    }
}