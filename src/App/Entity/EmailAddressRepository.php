<?php

namespace App\Entity;

use Doctrine\ORM\EntityRepository;

class EmailAddressRepository extends EntityRepository
{
    public function findByUserQuery(User $user)
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->setParameter('user', $user);
    }
}
