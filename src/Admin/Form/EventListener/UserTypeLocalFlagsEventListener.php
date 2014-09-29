<?php

namespace Admin\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Admin\Security\DefaultAuthorizationChecker;
use Symfony\Component\Form\FormEvent;

class UserTypeLocalFlagsEventListener implements EventSubscriberInterface
{
    private $authorizationChecker;

    public function __construct(DefaultAuthorizationChecker $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        if(!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_ADMIN')) {
            $form->remove('role');
        }
    }
}