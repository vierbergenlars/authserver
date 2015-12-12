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

use Admin\Form\BatchType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\NoRoute;

abstract class CRUDController extends BaseController
{

    /**
     * @View
     * @NoRoute
     */
    public function dashboardAction(Request $request)
    {
        return $this->paginate($this->getEntityRepository()->createQueryBuilder('e'), $request);
    }

    protected function createEditForm($entity)
    {
        return $this->createForm($this->getFormType(), $entity, array(
            'method' => 'PUT'
        ))
            ->add('submit', 'submit');
    }

    protected function createCreateForm()
    {
        return $this->createForm($this->getFormType(), $this->createNewEntity(), array(
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

    protected function handleDelete(Request $request, $object)
    {
        $form = $this->createDeleteForm();
        $form->handleRequest($request);
        if($this->isGranted('ROLE_API'))
            $form->submit(null);
        if(!$form->isValid())
            return $form;
        $this->getEntityManager()->remove($object);
        $this->getEntityManager()->flush();
        return null;
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

    /**
     * Handles a submission of a batch form
     * @param Request $request
     * @param array $callbacks Map of batch action names to a callback that gets called on the subjects array
     */
    protected function handleBatch(Request $request, array $callbacks = array())
    {
        $repository = $this->getEntityRepository();
        $form = $this->createBatchForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            $subjects = $form->get('subjects')->getData();
            $action = $form->get('action')->getData();
            if(isset($callbacks[$action])) {
                $callbacks[$action]($subjects);
            } else if ($action === 'DELETE') {
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

    protected function handleLink(Request $request, array $handlers = array())
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
                if($link instanceof \FOS\RestBundle\View\View)
                    $link = $link->getData();
                if(!isset($handlers[$type]))
                    throw new BadRequestHttpException(sprintf('Invalid relationship (allowed: %s)', implode(', ', array_keys($handlers))));

                call_user_func($handlers[$type], $link);
            }
        }
    }

    /**
     * @return AbstractType
     */
    abstract protected function getFormType();

    /**
     * @return EntityRepository
     */
    abstract protected function getEntityRepository();


    abstract protected function createNewEntity();

}
