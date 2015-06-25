<?php

namespace App\Controller\Api;

use App\Entity\Group;
use App\Entity\User;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController extends Controller
{
    /**
     * @View
     * @throws AccessDeniedException
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
            'user_id' => $user->getMigrateId(),
            'guid' => $user->getId(),
            'username' => $this->isGranted('ROLE_PROFILE:USERNAME')?$user->getUsername():null,
            'name' => $this->isGranted('ROLE_PROFILE:REALNAME')?$user->getDisplayName():null,
            'groups'   => $this->isGranted('ROLE_PROFILE:GROUPS')?$groups:array(),
        );
    }

    public function mailAction(Request $request)
    {
        if (!($user = $this->getUser())) {
            throw new AccessDeniedException();
        }
    }

}
