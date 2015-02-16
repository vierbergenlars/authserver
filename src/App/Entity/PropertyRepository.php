<?php

namespace App\Entity;

use App\Doctrine\EntityRepository;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;

class PropertyRepository extends EntityRepository
{
    public function create($object)
    {
        $this->getEntityManager()->beginTransaction();
        parent::create($object);
        $users = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('partial u.{id}')
                ->from('AppBundle:User', 'u')
                ->getQuery()
                ->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, 1)
                ->getResult();
        $userProperties = array();
        foreach ($users as $user) {
            $this->getEntityManager()->persist($userProperties[] = new UserProperty($user, $object));
        }
        $this->getEntityManager()->flush($userProperties);
        $this->getEntityManager()->commit();
    }

    public function search($terms)
    {
        $name = str_replace('*', '%', $terms);

        return new QueryBuilderPageDescription($this->createQueryBuilder('e')->where('e.name LIKE :name')->setParameter('name', '%'.$name.'%'));
    }
}
