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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $view->getContext()->setGroups(['admin_group_list_members', 'list']);
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
                'graph_form' => $this->createGraphForm($request, -1, false)->createView(),
            ));
        $view->getContext()->setGroups(['admin_group_list', 'list']);
        return $view;
    }

    /**
     * @View(serializerEnableMaxDepthChecks=true)
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

        $view = $this->view($group);
        $view->getContext()->setGroups(array('admin_group_object', 'object'));
        $view->setTemplateData(\Closure::bind(function() use($request) {
            return array(
                'graph_form' => $this->createGraphForm($request, 5, true)->createView(),
            );
        }, $this));
        return $view;
    }

    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $this->handleBatch($request);

        return $this->routeRedirectView('admin_group_gets', [], Response::HTTP_NO_CONTENT);
    }

    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['Exportable']['Enable'] = 'PATCH_exportable_true';
        $actions['Exportable']['Disable'] = 'PATCH_exportable_false';
        $actions['Member types']['Allow users'] = 'PATCH_noUsers_false';
        $actions['Member types']['Deny users'] = 'PATCH_noUsers_true';
        $actions['Member types']['Allow groups'] = 'PATCH_noGroups_false';
        $actions['Member types']['Deny groups'] = 'PATCH_noGroups_true';
        $actions['User leave/join']['Make user joinable'] = 'PATCH_userJoinable_true';
        $actions['User leave/join']['Make not user joinable'] = 'PATCH_userJoinable_false';
        $actions['User leave/join']['Make user leaveable'] = 'PATCH_userLeaveable_true';
        $actions['User leave/join']['Make not user leaveable'] = 'PATCH_userLeaveable_false';

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

        return $this->routeRedirectView('admin_group_get', array('group'=>$group->getName()), Response::HTTP_NO_CONTENT);
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

        return $this->routeRedirectView('admin_group_get', array('group'=>$form->getData()->getName()), Response::HTTP_CREATED);
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

        return $this->routeRedirectView('admin_group_gets', array(), Response::HTTP_NO_CONTENT);
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
        return GroupType::class;
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
        return $ff->createNamedBuilder('q', FormType::class, null, array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ))
            ->setMethod('GET')
            ->add('name', TextType::class, array(
                'required' => false,
            ))
            ->add('techname', TextType::class, array(
                'required' => false,
            ))
            ->add('exportable', ChoiceType::class, array(
                'choices' => array('No' => 0, 'Yes' => 1),
                'choices_as_values' => true,
                'expanded' => true,
                'required' => false,
            ))
            ->add('groups', ChoiceType::class, array(
                'choices' => array('No' => 0, 'Yes' => 1),
                'choices_as_values' => true,
                'expanded' => true,
                'required' => false,
            ))
            ->add('users', ChoiceType::class, array(
                'choices' => array('No' => 0, 'Yes' => 1),
                'choices_as_values' => true,
                'expanded' => true,
                'required' => false,
            ))
            ->add('userjoin', ChoiceType::class, array(
                'choices' => array('No' => 0, 'Yes' => 1),
                'choices_as_values' => true,
                'label' => 'User joinable',
                'expanded' => true,
                'required' => false,
            ))
            ->add('userleave', ChoiceType::class, array(
                'choices' => array('No' => 0, 'Yes' => 1),
                'choices_as_values' => true,
                'label' => 'User leaveable',
                'expanded' => true,
                'required' => false,
            ))
            ->add('search', SubmitType::class)
            ->getForm();
    }

    /**
     * @return FormInterface
     */
    private function createGraphForm(Request $request, $defaultDepth, $includeDirection)
    {
        $ff = $this->get('form.factory');
        /* @var $ff FormFactoryInterface */
        $builder =  $ff->createNamedBuilder('graph', FormType::class, array('depth' => $defaultDepth));
        if($includeDirection)
            $builder->add('direction', ChoiceType::class, array(
                'choices' => array(
                    'Members' => 'up',
                    'Parents' => 'down',
                    'Both' => 'both',
                ),
                'choices_as_values' => true,
            ));
        $builder->add('depth', IntegerType::class)
            ->add('Create graph', ButtonType::class, array(
                'attr' => array(
                    'class' => 'js--vizjs-load-graph'
                )
            ))
            ->setAction($this->generateUrl($request->attributes->get('_route'), array_merge($request->attributes->get('_route_params'), $request->query->all(), array('_format'=>'gv'))));
        return $builder->getForm();
    }
}
