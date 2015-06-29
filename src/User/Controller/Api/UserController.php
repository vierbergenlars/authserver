<?php

namespace User\Controller\Api;

use App\Entity\Group;
use App\Entity\User;
use FOS\RestBundle\Controller\Annotations\View;

class UserController extends BaseController
{
    /**
     * @View
     */
    public function getAction()
    {
        $user = $this->getUser();
        /* @var $user User */
        $exportableGroups = array_filter($user->getGroupsRecursive(), function(Group $group) {
            return $group->isExportable();
        });
        $groups = array_map(function(Group $group) {
            return $group->getName();
        }, $exportableGroups);

        return array(
            'guid' => $user->getGuid(),
            'username' => $this->isGrantedScope('profile:username')?$user->getUsername():null,
            'name' => $this->isGrantedScope('profile:realname')?$user->getDisplayName():null,
            'groups' => $this->isGrantedScope('profile:groups')?$groups:array(),
        );
    }
}
