<?php

namespace App\Entity;

use Doctrine\ORM\EntityRepository;

class GroupRepository extends EntityRepository
{

    public function getMembersQuery(Group $group, $recursive)
    {
        return $this->getMembersQueryBuilder($group, $recursive)->getQuery();
    }

    public function getMembersQueryBuilder(Group $group, $recursive)
    {
        return $this->getEntityManager()
                    ->createQueryBuilder()
                    ->select('u')
                    ->from('AppBundle:User', 'u')
                    ->innerJoin('u.groups', 'g')
                    ->where('g IN(:groups)')
                    ->setParameter('groups', $recursive ? $group->getMemberGroupsRecursive()
                        : $group);
    }
}
