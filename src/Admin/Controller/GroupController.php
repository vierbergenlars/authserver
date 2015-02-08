<?php

namespace Admin\Controller;

use Admin\Controller\Traits\Routes\LinkUnlinkTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Entity\Group;

class GroupController extends DefaultController
{
    use LinkUnlinkTrait;

    protected function handleLink($type, $parent, $link)
    {
        switch($type) {
            case 'group':
                $group = $link->getData();
                if(!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                if(!$parent->getGroups()->contains($group)) {
                    $parent->addGroup($group);
                }
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }

    protected function handleUnlink($type, $parent, $link)
    {
        switch($type) {
            case 'group':
                $group = $link->getData();
                if(!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                $parent->removeGroup($group);
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }

    protected function getBatchActions() {
        $actions = parent::getBatchActions();
        $actions['Exportable']['PATCH_exportable_true'] = 'Enable';
        $actions['Exportable']['PATCH_exportable_false'] = 'Disable';
        $actions['Member types']['PATCH_noUsers_false'] = 'Allow users';
        $actions['Member types']['PATCH_noUsers_true'] = 'Deny users';
        $actions['Member types']['PATCH_noGroups_false'] = 'Allow groups';
        $actions['Member types']['PATCH_noGroups_true'] = 'Deny groups';
        return $actions;
    }
}
