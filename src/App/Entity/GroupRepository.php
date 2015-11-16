<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
