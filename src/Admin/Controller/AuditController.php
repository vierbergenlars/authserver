<?php

namespace Admin\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View;
use Gedmo\Loggable\Entity\LogEntry;
use Symfony\Component\HttpFoundation\Request;

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

        if($request->query->has('target')) {
            @list($class, $id) = explode('@', $request->query->get('target'), 2);
            $queryBuilder->andWhere('l.objectClass = :oc')
                ->setParameter('oc', $class);
            if($id)
                $queryBuilder->andWhere('l.objectId = :oid')
                    ->setParameter('oid', $id);
        }
        if($request->query->has('username')) {
            if($request->query->get('username')) {
                $queryBuilder->andWhere('l.username = :username0 OR l.username LIKE :username1')
                    ->setParameter('username0', $request->query->get('username'))
                    ->setParameter('username1', '%;'.$request->query->get('username'));
            } else {
                $queryBuilder->andWhere('l.username is null');
            }
        }
        if($request->query->has('apikey')) {
            $queryBuilder->andWhere('l.username = :apikey0 OR l.username LIKE :apikey1')
                ->setParameter('apikey0', $request->query->get('apikey'))
                ->setParameter('apikey1', $request->query->get('apikey').';%');
        }

        return $this->view($this->paginate($queryBuilder, $request))
            ->setTemplateData([
                'entity_manager' => $this->getEntityManager(),
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
