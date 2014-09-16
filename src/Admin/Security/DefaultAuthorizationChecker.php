<?php

namespace Admin\Security;

use vierbergenlars\Bundle\RadRestBundle\Security\AuthorizationCheckerInterface;

class DefaultAuthorizationChecker implements AuthorizationCheckerInterface
{
    public function mayList()
    {
        return true;
    }

    public function mayView($o)
    {
        return true;
    }

    public function mayCreate()
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
}
