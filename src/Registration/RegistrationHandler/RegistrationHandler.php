<?php
namespace Registration\RegistrationHandler;

use Registration\RegistrationEvents;
use Registration\Event\RegistrationFormEvent;
use Registration\Event\RegistrationHandleEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class RegistrationHandler
{

    /**
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     *
     * @return FormInterface
     */
    public function getRegistrationForm()
    {
        $event = new RegistrationFormEvent();
        $this->eventDispatcher->dispatch(RegistrationEvents::BUILD_FORM, $event);
        return $event->getFormBuilder()->getForm();
    }

    public function handleRequest(Request $request)
    {
        $event = new RegistrationHandleEvent($this->getRegistrationForm());
        $event->getForm()->handleRequest($request);
        if ($event->getForm()->isValid())
            $this->eventDispatcher->dispatch(RegistrationEvents::HANDLE_FORM, $event);

        return $event->isSucceeded() ? null : $event->getForm();
    }
}
