<?php

namespace App\Security\User;

use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserProvider extends EntityUserProvider
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, 'AppBundle:User', 'username');
    }
}
