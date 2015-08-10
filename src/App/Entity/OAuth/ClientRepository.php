<?php

namespace App\Entity\OAuth;

use App\Doctrine\EntityRepository;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;

class ClientRepository extends EntityRepository
{
    public function search($terms)
    {
        $name = str_replace('*', '%', $terms);

        return new QueryBuilderPageDescription($this->createQueryBuilder('e')->where('e.name LIKE :name')->setParameter('name', '%'.$name.'%'));
    }
}
