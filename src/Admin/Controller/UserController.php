<?php

namespace Admin\Controller;

use App\Entity\Group;
use Admin\Controller\Traits\Routes\LinkUnlinkTrait;
use vierbergenlars\Bundle\RadRestBundle\Manager\SecuredResourceManager;
use Admin\Security\DefaultAuthorizationChecker;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserController extends DefaultController
{
    use LinkUnlinkTrait;

    protected function handleLink($type, $user, $link)
    {
        switch($type) {
            case 'group':
                if(!$this->hasRole('ROLE_SCOPE_W_PROFILE_GROUPS')) {
                    throw new AccessDeniedException();
                }
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
                if(!$this->hasRole('ROLE_SCOPE_W_PROFILE_GROUPS')) {
                    throw new AccessDeniedException();
                }

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
        if($action == 'get') {
            if($this->hasRole('ROLE_SCOPE_R_PROFILE_EMAIL')) {
                $groups[] = 'admin_user_object_scope_email';
            }
        }
        return $groups;
    }
    
    private function hasRole($role) {
        if(($rm = $this->getResourceManager()) instanceof SecuredResourceManager) {
            /* @var $rm SecuredResourceManager */
            if(($ac = $rm->getAuthorizationChecker()) instanceof DefaultAuthorizationChecker) {
                /* @var $ac DefaultAuthorizationChecker */
                return $ac->hasRole($role);
            }
        }
        return false;
    }

}
