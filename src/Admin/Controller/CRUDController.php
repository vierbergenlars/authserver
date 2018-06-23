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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\NoRoute;
use Admin\Event\BatchEvent;
use Admin\AdminEvents;
use Admin\Event\FilterListEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Admin\Event\DisplayListEvent;

abstract class CRUDController extends BaseController
{

    /**
     * @View
     * @NoRoute
     */
    public function dashboardAction(Request $request)
    {
        return $this->paginate($this->getEntityRepository()
            ->createQueryBuilder('e'), $request);
    }

    /**
     *
     * @return FilterListEvent
     */
    protected function dispatchFilter(FormBuilderInterface $searchFormBuilder = null)
    {
        if (!$searchFormBuilder) {
            $ff = $this->get('form.factory');
            /* @var $ff \Symfony\Component\Form\FormFactoryInterface */
            $searchFormBuilder = $ff->createNamedBuilder('q', FormType::class, null, array(
                'csrf_protection' => false,
                'allow_extra_fields' => true
            ))->setMethod('GET');
        }

        $event = new FilterListEvent($this->getEntityType(), $searchFormBuilder);
        $this->get('event_dispatcher')->dispatch(AdminEvents::FILTER_LIST, $event);

        return $event;
    }

    protected function getDisplayListEvent()
    {
        return new DisplayListEvent($this->getEntityType());
    }

    /**
     * @param $entity
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function createEditForm($entity)
    {
        return $this->createForm($this->getFormType(), $entity, array(
            'method' => 'PUT'
        ))
            ->add('submit', SubmitType::class);
    }

    protected function createCreateForm()
    {
        return $this->createForm($this->getFormType(), $this->createNewEntity(), array(
            'method' => 'POST'
        ))
            ->add('submit', SubmitType::class);
    }

    protected function createDeleteForm()
    {
        return $this->createFormBuilder()
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array(
                'button_class' => 'danger',
                'label' => 'Delete',
            ))
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

    private function getBatchEvent()
    {
        $event = new BatchEvent($this->getEntityType());
        $em = $this->getEntityManager();
        $actions = [];
        // Normalize potentially doubly nested array
        foreach ($this->getBatchActions() as $label => $nameOrArray) {
            if (is_array($nameOrArray)) {
                foreach ($nameOrArray as $label2 => $name) {
                    $actions[$name] = [
                        $label,
                        $label2
                    ];
                }
            } else {
                $actions[$nameOrArray] = $label;
            }
        }
        foreach ($actions as $name => $label) {
            if ($name === "DELETE") {
                $event->setAction($name, $label, function ($object) use ($em) {
                    $em->remove($object);
                });
            } elseif (substr($name, 0, 6) === "PATCH_") {
                list ($_, $property, $value) = explode('_', $name);
                $event->setAction($name, $label, function ($object) use ($property, $value) {
                    $method = 'set' . ucfirst($property);
                    $object->$method(json_decode($value));
                });
            }
        }
        $this->get('event_dispatcher')->dispatch(AdminEvents::BATCH_ACTIONS, $event);
        return $event;
    }

    protected function createBatchForm($batchEvent = null)
    {
        if (!$batchEvent) {
            $batchEvent = $this->getBatchEvent();
        }
        return $this->createForm(BatchType::class, null, [
            'actions' => $batchEvent->getChoices()
        ]);
    }

    protected function getBatchActions()
    {
        return array(
            'Delete' => 'DELETE',
        );
    }

    /**
     * Handles a submission of a batch form
     * @param Request $request
     */
    protected function handleBatch(Request $request)
    {
        $repository = $this->getEntityRepository();
        $event = $this->getBatchEvent();
        $form = $this->createBatchForm($event);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $subjects = $form->get('subjects')->getData();
            $action = $form->get('action')->getData();

            $checkedSubjects = [];
            foreach ($subjects as $id => $checked) {
                if ($checked) {
                    $checkedSubjects[] = $id;
                }
            }

            $entitySubjects = $repository->createQueryBuilder('e')
                ->andWhere('e.id IN(:ids)')
                ->setParameter('ids', $checkedSubjects)
                ->getQuery()
                ->getResult();

            $event->handleAction($action, $entitySubjects);
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
     *
     * @return string
     */
    private function getEntityType()
    {
        return $this->getEntityRepository()->getClassName();
    }

    /**
     *
     * @return string
     */
    abstract protected function getFormType();

    /**
     *
     * @return EntityRepository
     */
    abstract protected function getEntityRepository();

    abstract protected function createNewEntity();
}
