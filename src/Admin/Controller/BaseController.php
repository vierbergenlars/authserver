<?php

namespace Admin\Controller;

use Admin\Form\BatchType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseController extends FOSRestController
{
    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @param QueryBuilder $qb
     * @param Request $request
     * @return PaginationInterface
     */
    protected function paginate(QueryBuilder $qb, Request $request)
    {
        $page = (int)$request->query->get('page', 1);
        $size = (int)$request->query->get('per_page', 10);
        if($page <= 0)
            throw new BadRequestHttpException('The page parameter should be a positive number.');
        if($size <= 0)
            throw new BadRequestHttpException('The per_page parameter should be a positive number.');
        if($size > 1000)
            throw new BadRequestHttpException('The per_page parameter should not exceed 1000.');

        return $this->get('knp_paginator')->paginate($qb, $page, $size);
    }
}
