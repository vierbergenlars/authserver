<?php

namespace App\Entity;

use App\Doctrine\EntityRepository;

class PropertyRepository extends EntityRepository
{
    protected $fieldSearchWhitelist = array();
    public function create($object) {
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
        foreach($users as $user) {
            $this->getEntityManager()->persist($userProperties[] = new UserProperty($user, $object));
        }
        $this->getEntityManager()->flush($userProperties);
        $this->getEntityManager()->commit();
    }
}
