<?php

namespace App\Entity\OAuth;

use App\Doctrine\EntityRepository;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;

class ClientRepository extends EntityRepository
{
    public function delete($object)
    {
        $this->getEntityManager()->beginTransaction();
        $this->getEntityManager()->createQueryBuilder()
            ->delete('AppBundle:OAuth\AccessToken', 'at')
            ->where('at.client = :client')
            ->setParameter('client', $object)
            ->getQuery()
            ->execute();
        $this->getEntityManager()->createQueryBuilder()
            ->delete('AppBundle:OAuth\RefreshToken', 'rt')
            ->where('rt.client = :client')
            ->setParameter('client', $object)
            ->getQuery()
            ->execute();
        $this->getEntityManager()->createQueryBuilder()
            ->delete('AppBundle:OAuth\AuthCode', 'ac')
            ->where('ac.client = :client')
            ->setParameter('client', $object)
            ->getQuery()
            ->execute();
        parent::delete($object);
        $this->getEntityManager()->commit();
    }

    public function search($terms)
    {
        $name = str_replace('*', '%', $terms);
        return new QueryBuilderPageDescription($this->createQueryBuilder('e')->where('e.name LIKE :name')->setParameter('name', '%'.$name.'%'));
    }
}
