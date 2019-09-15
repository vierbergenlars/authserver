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

namespace Admin\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View;
use Gedmo\Loggable\Entity\LogEntry;
use Symfony\Component\HttpFoundation\Request;
use Admin\Event\DisplayListEvent;

class AuditController extends BaseController
{
    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getRepository()
    {
        return $this->getEntityManager()->getRepository('Gedmo\Loggable\Entity\LogEntry');
    }

    /**
     * @View
     * @Get(name="s")
     */
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('l')
            ->orderBy('l.loggedAt', 'DESC');

        if ($request->query->has('target')) {
            @list ($class, $id) = explode('@', $request->query->get('target'), 2);
            $queryBuilder->andWhere('l.objectClass = :oc')->setParameter('oc', $class);
            if ($id)
                $queryBuilder->andWhere('l.objectId = :oid')->setParameter('oid', $id);
        }
        if ($request->query->has('username')) {
            if ($request->query->get('username')) {
                $queryBuilder->andWhere('l.username = :username0 OR l.username LIKE :username1')
                    ->setParameter('username0', $request->query->get('username'))
                    ->setParameter('username1', '%;' . $request->query->get('username'));
            } else {
                $queryBuilder->andWhere('l.username is null');
            }
        }
        if ($request->query->has('apikey')) {
            $queryBuilder->andWhere('l.username = :apikey0 OR l.username LIKE :apikey1')
                ->setParameter('apikey0', $request->query->get('apikey'))
                ->setParameter('apikey1', $request->query->get('apikey') . ';%');
        }

        $data = $this->paginate($queryBuilder, $request);

        $displayListEvent = new DisplayListEvent(LogEntry::class, $data);
        $displayListEvent->setGlobalData([
            'entity_manager' => $this->getEntityManager()
        ]);

        return $this->view($data)->setTemplateData([
            'display_list_event' => $displayListEvent
        ]);
    }

    /**
     * @View
     */
    public function getAction(LogEntry $logEntry)
    {
        return $this->view($logEntry)
            ->setTemplateData([
                'entity_manager' => $this->getEntityManager(),
            ]);
    }
}
