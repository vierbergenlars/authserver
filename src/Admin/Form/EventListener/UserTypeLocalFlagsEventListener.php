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
        $data = $event->getData();
        if (!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_ADMIN')) {
            $form->remove('role');
        }
        if($form->getConfig()->getMethod() !== 'POST') {
            if (!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_CRED')) {
                $form->remove('password');
                $form->remove('passwordEnabled');
            }
            if (!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_EMAIL')) {
                $form->remove('emailAddresses');
            }
            if (!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_GROUPS')) {
                $form->remove('groups');
            }
            if (!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_USERNAME')) {
                $form->remove('username');
            }
            if (!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_ENABLED')) {
                $form->remove('enabled');
            }
        }
        if ($this->authorizationChecker->hasRole('ROLE_API')) {
            // Remove user properties fields when logged in with API key
            $form->remove('userProperties');
            $form->remove('groups');
        }
        if ($data instanceof \App\Entity\User) {
            /* @var $data \App\Entity\User */
            if ($data->getRole() == 'ROLE_SUPER_ADMIN' &&!$this->authorizationChecker->hasRole('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')) {
                $form->remove('enabled');
            }
        }
    }
}
