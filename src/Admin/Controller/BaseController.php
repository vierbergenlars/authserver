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

    protected function createBatchForm()
    {
        return $this->createForm(new BatchType($this->getBatchActions()));
    }

    protected function getBatchActions()
    {
        return array(
            'DELETE' => 'Delete',
        );
    }

    protected function handleBatch(Request $request, EntityRepository $repository)
    {
        $form = $this->createBatchForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $subjects = $form->get('subjects')->getData();
            $action = $form->get('action')->getData();
            if ($action === 'DELETE') {
                foreach ($subjects as $id => $checked) {
                    if ($checked) {
                        $this->getEntityManager()->remove($repository->find($id));
                    }
                }
            } elseif (substr($action, 0, 6) === 'PATCH_') {
                list($_, $property, $value) = explode('_', $action);
                foreach ($subjects as $id => $checked) {
                    if ($checked) {
                        $group = $repository->find($id);
                        $method = 'set' . ucfirst($property);
                        $group->$method(json_decode($value));
                    }
                }
            }
            $this->getEntityManager()->flush();
        }
    }

    protected function createEditForm(AbstractType $type, $entity)
    {
        return $this->createForm($type, $entity, array(
            'method' => 'PUT'
        ))
        ->add('submit', 'submit');
    }

    protected function createCreateForm(AbstractType $type, $entity)
    {
        return $this->createForm($type, $entity, array(
            'method' => 'POST'
        ))
        ->add('submit', 'submit');
    }

    protected function createDeleteForm()
    {
        return $this->createFormBuilder()
            ->setMethod('DELETE')
            ->add('submit', 'submit')
            ->getForm();
    }

    protected function handleLink(Request $request, array $handlers)
    {
        if(!$request->attributes->has('links'))
            throw new BadRequestHttpException('Missing Link header');
        foreach($request->attributes->get('links') as $type => $links) {
            if(!is_array($links))
                throw new BadRequestHttpException('Missing rel on Link header');
            foreach($links as $link) {
                if (is_string($link)) {
                    throw $this->createNotFoundException(sprintf('Subresource for "%s" not found', $link));
                }
                if(!isset($handlers[$type]))
                    throw new BadRequestHttpException(sprintf('Invalid relationship (allowed: %s)', implode(', ', array_keys($handlers))));

                call_user_func($handlers[$type], $link);
            }
        }
    }

}
