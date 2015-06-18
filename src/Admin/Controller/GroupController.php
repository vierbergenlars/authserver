<?php

namespace Admin\Controller;

use Admin\Controller\Traits\Routes\LinkUnlinkTrait;
use App\Entity\Group;
use App\Entity\GroupRepository;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View as AView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use vierbergenlars\Bundle\RadRestBundle\View\View;

class GroupController extends DefaultController
{
    use LinkUnlinkTrait;

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
        $members = $repo->getMembersQuery($group, $request->query->has('all'));
        $view    = View::create($this->getPaginator()->paginate($members, $request->query->get('page', 1)));
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
