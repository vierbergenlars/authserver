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

use App\Entity\Group;
use App\Entity\GroupRepository;
use App\Form\GroupType;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @ParamConverter("group",options={"mapping":{"group":"name"}})
 */
class GroupController extends CRUDController
{
    public function flagsAction(Request $request, Group $group)
    {
        $form = $this->get('form.factory')
            ->createNamedBuilder('', 'form', $group)
            ->add('exportable', 'choice', array(
                'choices' => array(false => 0, true => 1)
            ))
            ->add('userJoinable', 'choice', array(
                'choices' => array(false => 0, true => 1)
            ))
            ->add('userLeaveable', 'choice', array(
                'choices' => array(false => 0, true => 1)
            ))
            ->add('noUsers', 'choice', array(
                'choices' => array(false => 0, true => 1)
            ))
            ->add('noGroups', 'choice', array(
                'choices' => array(false => 0, true => 1)
            ))
            ->setMethod('PATCH')
            ->getForm();
        /* @var $form Form */

        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->flush();

        return null;
    }

    public function displaynameAction(Request $request, Group $group)
    {
        $form = $this->createEditForm($group);
        $form->submit(array('displayName' => $request->getContent()), false);
        if(!$form->isValid())
            return $form->get('displayName');
        $this->getEntityManager()->flush();
        return null;
    }

    /**
     * @View
     * @Get(path="/{group}/members")
     */
    public function getMembersAction(Request $request, Group $group)
    {
        /* @var $group Group */
        $repo  = $this->getEntityManager()->getRepository('AppBundle:Group');

        /* @var $repo GroupRepository */
        $members = $repo->getMembersQueryBuilder($group, $request->query->has('all'));

        $pagination = $this->paginate($members, $request);
        $view = $this->view($pagination)
            ->setTemplateData(array(
            'group' => $group,
        ));
        $view->getSerializationContext()->setGroups(['admin_group_list_members', 'list']);
        return $view;
    }

    /**
     * @View(serializerEnableMaxDepthChecks=true)
     * @Get(name="s")
     */
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:Group')
            ->createQueryBuilder('g')
            ->orderBy('g.id', 'DESC');

        $searchForm = $this->createSearchForm()->handleRequest($request);

        if($searchForm->isValid()) {
            if(!$searchForm->get('name')->isEmpty())
                $queryBuilder->andWhere('g.displayName LIKE :displayName')
                    ->setParameter('displayName', $searchForm->get('name')->getData());
            if(!$searchForm->get('techname')->isEmpty())
                $queryBuilder->andWhere('g.name LIKE :name')
                    ->setParameter('name', $searchForm->get('techname')->getData());
            if($searchForm->get('exportable')->getData() !== null)
                $queryBuilder->andWhere('g.exportable = :exportable')
                    ->setParameter('exportable', !!$searchForm->get('exportable')->getData());
            if($searchForm->get('groups')->getData() !== null)
                $queryBuilder->andWhere('g.noGroups = :noGroups')
                    ->setParameter('noGroups', !$searchForm->get('groups')->getData());
            if($searchForm->get('users')->getData() !== null)
                $queryBuilder->andWhere('g.noUsers = :noUsers')
                    ->setParameter('noUsers', !$searchForm->get('users')->getData());
            if($searchForm->get('userjoin')->getData() !== null)
                $queryBuilder->andWhere('g.userJoinable = :userJoinable')
                    ->setParameter('userJoinable', !!$searchForm->get('userjoin')->getData());
            if($searchForm->get('userleave')->getData() !== null)
                $queryBuilder->andWhere('g.userLeaveable = :userLeaveable')
                    ->setParameter('userLeaveable', !!$searchForm->get('userleave')->getData());
        }

        if($request->attributes->get('_format') === 'gv') {
            $request->setRequestFormat('gv');
            return $this->view($queryBuilder->getQuery()->getResult())
                ->setTemplateData(array(
                    'link_map' => new \ArrayObject(),
                    'groups' => new \ArrayObject(),
                    'depth' => (int)$request->query->get('depth', -1),
                    'request' => $request,
                ));
        }

        $view = $this->view($this->paginate($queryBuilder, $request))
            ->setTemplateData(array(
                'batch_form'=>$this->createBatchForm()->createView(),
                'search_form' => $searchForm->createView(),
            ));
        $view->getSerializationContext()->setGroups(['admin_group_list', 'list']);
        return $view;
    }

    /**
     * @View(serializerGroups={"admin_group_object", "object"}, serializerEnableMaxDepthChecks=true)
     */
    public function getAction(Group $group, Request $request)
    {
        if($request->attributes->get('_format') === 'gv') {
            $request->setRequestFormat('gv');
            return $this->view($group)
                ->setTemplateData(array(
                    'depth' => (int)$request->query->get('depth', 5),
                    'direction' => $request->query->get('direction', 'both'),
                    'link_map' => new \ArrayObject(),
                    'groups' => new \ArrayObject(),
                ));
        }

        return $group;
    }

    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $this->handleBatch($request);

        return $this->routeRedirectView('admin_group_gets');
    }

    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['Exportable']['PATCH_exportable_true'] = 'Enable';
        $actions['Exportable']['PATCH_exportable_false'] = 'Disable';
        $actions['Member types']['PATCH_noUsers_false'] = 'Allow users';
        $actions['Member types']['PATCH_noUsers_true'] = 'Deny users';
        $actions['Member types']['PATCH_noGroups_false'] = 'Allow groups';
        $actions['Member types']['PATCH_noGroups_true'] = 'Deny groups';
        $actions['User leave/join']['PATCH_userJoinable_true'] = 'Make user joinable';
        $actions['User leave/join']['PATCH_userJoinable_false'] = 'Make not user joinable';
        $actions['User leave/join']['PATCH_userLeaveable_true'] = 'Make user leaveable';
        $actions['User leave/join']['PATCH_userLeaveable_false'] = 'Make not user leaveable';

        return $actions;
    }

    /**
     * @View
     */
    public function editAction(Group $group)
    {
        return $this->createEditForm($group);
    }

    /**
     * @View(template="AdminBundle:Group:edit.html.twig")
     */
    public function putAction(Request $request, Group $group)
    {
        $form = $this->createEditForm($group);
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_group_get', array('group'=>$group->getName()), Codes::HTTP_NO_CONTENT);
    }

    /**
     * @View
     */
    public function newAction()
    {
        return $this->createCreateForm();
    }

    /**
     * @View(template="AdminBundle:Group:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->createCreateForm();
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->persist($form->getData());
        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_group_get', array('group'=>$form->getData()->getName()));
    }

    /**
     * @View
     */
    public function removeAction(Group $group)
    {
        return $this->createDeleteForm();
    }

    /**
     * @View(template="AdminBundle:Group:remove.html.twig")
     */
    public function deleteAction(Request $request, Group $group)
    {
        $ret = $this->handleDelete($request, $group);
        if($ret)
            return $ret;

        return $this->routeRedirectView('admin_group_gets', array(), Codes::HTTP_NO_CONTENT);
    }

    public function linkAction(Request $request, Group $group)
    {
        $this->handleLink($request, array(
            'group' => function($parent) use ($group) {
                if(!$parent instanceof Group)
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                if(!$group->getGroups()->contains($parent))
                    $group->addGroup($parent);
            }
        ));
        $this->getEntityManager()->flush();
    }

    public function unlinkAction(Request $request, Group $group)
    {
        $this->handleLink($request, array(
            'group' => function($parent) use ($group) {
                if(!$parent instanceof Group)
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                $group->removeGroup($parent);
            }
        ));
        $this->getEntityManager()->flush();
    }

    /**
     * @return EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository('AppBundle:Group');
    }

    /**
     * @return AbstractType
     */
    protected function getFormType()
    {
        return new GroupType();
    }

    protected function createNewEntity()
    {
        return new Group();
    }

    /**
     * @return FormInterface
     */
    private function createSearchForm()
    {
        $ff = $this->get('form.factory');
        /* @var $ff FormFactoryInterface */
        return $ff->createNamedBuilder('q', 'form', null, array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ))
            ->setMethod('GET')
            ->add('name', 'text', array(
                'required' => false,
            ))
            ->add('techname', 'text', array(
                'required' => false,
            ))
            ->add('exportable', 'choice', array(
                'choices' => array(0=>'No', 1=>'Yes'),
                'expanded' => true,
                'required' => false,
            ))
            ->add('groups', 'choice', array(
                'choices' => array(0=>'No', 1=>'Yes'),
                'expanded' => true,
                'required' => false,
            ))
            ->add('users', 'choice', array(
                'choices' => array(0=>'No', 1=>'Yes'),
                'expanded' => true,
                'required' => false,
            ))
            ->add('userjoin', 'choice', array(
                'choices' => array(0=>'No', 1=>'Yes'),
                'label' => 'User joinable',
                'expanded' => true,
                'required' => false,
            ))
            ->add('userleave', 'choice', array(
                'choices' => array(0=>'No', 1=>'Yes'),
                'label' => 'User leaveable',
                'expanded' => true,
                'required' => false,
            ))
            ->add('search', 'submit')
            ->getForm();
    }
}
