<?php

namespace Admin\Security;

use vierbergenlars\Bundle\RadRestBundle\Security\AbstractAuthorizationChecker;

class DefaultAuthorizationChecker extends AbstractAuthorizationChecker
{
    public function mayList()
    {
        return true;
    }

    public function mayView($o)
    {
        return true;
    }

    public function mayCreate($o)
    {
        return true;
    }

    public function mayEdit($o)
    {
        return true;
    }

    public function mayDelete($o)
    {
        return true;
    }

    public function hasRole($role)
    {
        return parent::hasRole($role);
    }
}
