<?php

namespace Admin\Entity;

use App\Doctrine\EntityRepository;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;

class ApiKeyRepository extends EntityRepository
{
    public function search($terms)
    {
        $name = str_replace('*', '%', $terms);

        return new QueryBuilderPageDescription($this->createQueryBuilder('e')->where('e.name LIKE :name')->setParameter('name', '%'.$name.'%'));
    }
}
