<?php

namespace App\Doctrine;

use vierbergenlars\Bundle\RadRestBundle\Doctrine\EntityRepository as BaseRepository;

class EntityRepository extends BaseRepository
{
    public function create()
    {
        $n = $this->getClassName();
        return new $n();
    }
}