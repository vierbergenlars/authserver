<?php

namespace App\Manager;

use vierbergenlars\Bundle\RadRestBundle\Manager\SecuredResourceManager as BaseSRM;

class SecuredResourceManager extends BaseSRM
{
    public function search($terms)
    {
        if (!$this->getAuthorizationChecker()->mayList()) {
            throw new AccessDeniedException();
        }

        return $this->getResourceManager()->search($terms);
    }
}
