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

}
