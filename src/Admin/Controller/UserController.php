<?php

namespace Admin\Controller;

use App\Entity\Group;
use Admin\Controller\Traits\Routes\LinkUnlinkTrait;
use vierbergenlars\Bundle\RadRestBundle\View\View;
use vierbergenlars\Bundle\RadRestBundle\Manager\SecuredResourceManager;
use Admin\Security\DefaultAuthorizationChecker;

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

    public function getSerializationGroups($action)
    {
        $groups = parent::getSerializationGroups($action);
        $resourceManager = $this->getResourceManager();
        if($action == 'get' && $resourceManager instanceof SecuredResourceManager) {
            $authorizationChecker = $resourceManager->getAuthorizationChecker();
            if($authorizationChecker instanceof DefaultAuthorizationChecker) {
                if($authorizationChecker->hasRole('ROLE_SCOPE_R_PROFILE_EMAIL')) {
                    $groups[] = 'admin_user_object_scope_email';
                }

            }
        }
        return $groups;
    }
}
