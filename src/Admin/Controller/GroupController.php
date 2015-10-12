<?php

namespace Admin\Controller;

use App\Entity\Group;
use App\Entity\GroupRepository;
use App\Form\GroupType;
use App\Search\SearchFieldException;
use App\Search\SearchGrammar;
use App\Search\SearchValueException;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @ParamConverter("group",options={"mapping":{"group":"name"}})
 */
class GroupController extends CRUDController
{
    /**
     * @ApiDoc
     */
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

    /**
     * @ApiDoc
     */
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
     * @Get(path="/{group}/members")
     * @View(serializerGroups={"admin_group_list_members","list"})
     * @ApiDoc
     */
    public function getMembersAction(Request $request, Group $group)
    {
        /* @var $group Group */
        $repo  = $this->getEntityManager()->getRepository('AppBundle:Group');

        /* @var $repo GroupRepository */
        $members = $repo->getMembersQueryBuilder($group, $request->query->has('all'));

        $pagination = $this->paginate($members, $request);
        return $this->view($pagination)->setTemplateData(array(
            'group' => $group,
        ));
    }

    /**
     * @ApiDoc
     * @View(serializerGroups={"admin_group_list", "list"})
     * @Get(name="s")
     */
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:Group')
            ->createQueryBuilder('g');

        $query = $request->query->get('q', array());
        if (is_string($query)) {
            $parser = new SearchGrammar();
            $blocks = $parser->parse($query);
        } elseif (is_array($query)) {
            $blocks = array();
            foreach ($query as $name=>$value) {
                if(is_array($value)) {
                    foreach($value as $v) {
                        $blocks[] = array(
                            'name'=>$name,
                            'value'=>$v,
                        );
                    }
                } else {
                    $blocks[] = array(
                        'name' => $name,
                        'value' => $value,
                    );
                }
            }
        } else {
            throw new BadRequestHttpException;
        }

        foreach($blocks as $block) {
            switch ($block['name']) {
                case 'name':
                    if(strpos($block['value'], '*') !== false)
                        $queryBuilder->andWhere('g.displayName LIKE :displayName');
                    else
                        $queryBuilder->andWhere('g.displayName = :displayName');
                    $queryBuilder->setParameter('displayName', str_replace('*', '%',$block['value']));
                    break;
                case 'techname':
                    if(strpos($block['value'], '*') !== false)
                        $queryBuilder->andWhere('g.name LIKE :name');
                    else
                        $queryBuilder->andWhere('g.name = :name');
                    $queryBuilder->setParameter('name', str_replace('*', '%', $block['value']));
                    break;
                case 'is':
                    switch (strtolower($block['value'])) {
                        case 'exportable':
                            $queryBuilder->andWhere('g.exportable = true');
                            break;
                        case 'not exportable':
                        case 'noexportable':
                            $queryBuilder->andWhere('g.exportable = false');
                            break;
                        case 'nogroups':
                            $queryBuilder->andWhere('g.noGroups = true');
                            break;
                        case 'groups':
                            $queryBuilder->andWhere('g.noGroups = false');
                            break;
                        case 'nousers':
                            $queryBuilder->andWhere('g.noUsers = true');
                            break;
                        case 'users':
                            $queryBuilder->andWhere('g.noUsers = false');
                            break;
                        case 'userjoin':
                            $queryBuilder->andWhere('g.userJoinable = true');
                            break;
                        case 'nouserjoin':
                            $queryBuilder->andWhere('g.userJoinable = false');
                            break;
                        case 'userleave':
                            $queryBuilder->andWhere('g.userLeaveable = true');
                            break;
                        case 'nouserleave':
                            $queryBuilder->andWhere('g.userLeaveable = false');
                            break;
                        default:
                            throw new SearchValueException($block['name'], $block['value'], array('exportable', 'not exportable', 'nogroups', 'groups', 'nousers', 'users'));
                    }
                    break;
                default:
                    throw new SearchFieldException($block['name'], array());
            }
        }

        return $this->view($this->paginate($queryBuilder, $request))->setTemplateData(array('batch_form'=>$this->createBatchForm()->createView()));
    }

    /**
     * @ApiDoc
     * @View(serializerGroups={"admin_group_object", "object"})
     */
    public function getAction(Group $group)
    {
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
}
