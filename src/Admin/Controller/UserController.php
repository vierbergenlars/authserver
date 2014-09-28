<?php

namespace Admin\Controller;

use App\Entity\Group;
use Admin\Controller\Traits\Routes\LinkUnlinkTrait;

class UserController extends DefaultController
{
    use LinkUnlinkTrait;

    protected function handleLink($type, $user, $link)
    {
        switch($type) {
            case 'group':
                $group = $link->getData();
                if(!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                if(!$user->getGroups()->contains($group)) {
                    $user->addGroup($group);
                }
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }

    protected function handleUnlink($type, $user, $link)
    {
        switch($type) {
            case 'group':
                $group = $link->getData();
                if(!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                $user->removeGroup($group);
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }
}
