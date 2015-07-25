<?php

namespace Admin\Controller;

use Admin\Controller\Traits\Routes\LinkUnlinkTrait;
use App\Entity\Group;
use App\Entity\GroupRepository;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View as AView;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;
use vierbergenlars\Bundle\RadRestBundle\View\View;

class GroupController extends DefaultController
{
    use LinkUnlinkTrait;

    /**
     * @ApiDoc
     */
    public function flagsAction(Request $request, Group $id)
    {
        $form = $this->getFormFactory()->createNamedBuilder('', 'form', $id)
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

        $this->getResourceManager()->update($id);
        return null;
    }

    /**
     * @ApiDoc
     */
    public function displaynameAction(Request $request, Group $id)
    {
        $form = $this->createEditForm($id);
        $form->submit(array('displayName' => $request->getContent()), false);
        if(!$form->isValid())
            return $form->get('displayName');
        $this->getResourceManager()->update($id);
        return null;
    }

    protected function handleLink($type, $parent, $link)
    {
        switch ($type) {
            case 'group':
                $group = $link->getData();
                if (!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                if (!$parent->getGroups()->contains($group)) {
                    $parent->addGroup($group);
                }
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }

    protected function handleUnlink($type, $parent, $link)
    {
        switch ($type) {
            case 'group':
                $group = $link->getData();
                if (!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                $parent->removeGroup($group);
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
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
     * @Get(path="/{id}/members")
     * @AView
     */
    public function getMembersAction(Request $request, $id)
    {
        $group = $this->getAction($id)->getData();
        /* @var $group Group */
        $repo  = $this->getResourceManager();

        if (!$repo instanceof GroupRepository) {
            throw new HttpException(503);
        }
        /* @var $repo GroupRepository */
        $members = $repo->getMembersQueryBuilder($group, $request->query->has('all'));
        $pageDescription = new QueryBuilderPageDescription($members);
        $view = View::create($this->getPagination($pageDescription, $request->query->get('page', 1), $request->query->get('per_page', 10)));
        $view->setExtraData(array(
            'group' => $group,
        ));
        $view->getSerializationContext()->setGroups($this->getSerializationGroups('get_members'));

        return $this->handleView($view);
    }

    public function getSerializationGroups($action)
    {
        switch($action) {
            case 'get_members':
                return array(
                    'admin_group_list_members',
                    'list',
                );
            default:
                return parent::getSerializationGroups($action);
        }
    }
}
