<?php

namespace App\Controller\Api;

use App\Entity\Group;
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
        if (!($user = $this->getUser())) {
            throw new AccessDeniedException();
        }

        $exportableGroups = array_filter($user->getGroupsRecursive(), function(Group $group) {
            return $group->isExportable();
        });
        $groups = array_map(function(Group $group) {
            return $group->getName();
        }, $exportableGroups);

        return array(
            'user_id' => $user->getMigrateId(),
            'guid' => $user->getId(),
            'username' => $user->getUsername(),
            'name' => $user->getDisplayName(),
            'groups'   => $groups,
        );
    }

    public function mailAction(Request $request)
    {
        if (!($user = $this->getUser())) {
            throw new AccessDeniedException();
        }
    }

}
