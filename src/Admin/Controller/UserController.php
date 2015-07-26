<?php

namespace Admin\Controller;

use App\Entity\Group;
use Admin\Controller\Traits\Routes\LinkUnlinkTrait;
use App\Entity\User;
use App\Entity\UserProperty;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use vierbergenlars\Bundle\RadRestBundle\Manager\SecuredResourceManager;
use Admin\Security\DefaultAuthorizationChecker;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\Annotations\Patch;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class UserController extends DefaultController
{
    use LinkUnlinkTrait;

    /**
     * @ApiDoc
     * @Security("(has_role('ROLE_SCOPE_W_PROFILE_ENABLED') and id.getRole() not in ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']) or has_role('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')")
     */
    public function enableAction(User $id)
    {
        $id->setEnabled(true);
        $this->getResourceManager()->update($id);
    }

    /**
     * @ApiDoc
     * @Security("(has_role('ROLE_SCOPE_W_PROFILE_ENABLED') and id.getRole() not in ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']) or has_role('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')")
     */
    public function disableAction(User $id)
    {
        $id->setEnabled(false);
        $this->getResourceManager()->update($id);
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/property/{property}")
     */
    public function propertyAction(Request $request, User $id, $property)
    {
        $userProperty = $id->getUserProperties()->filter(function(UserProperty $up) use($property){
            return $up->getProperty()->getName() === $property;
        })->first();
        /* @var $userProperty UserProperty */
        if($userProperty === null)
            throw new NotFoundHttpException;

        $regex = $userProperty->getProperty()->getValidationRegex();
        $value = $request->getContent();
        if(!preg_match($regex, $value))
            throw new BadRequestHttpException('The given value does not match '.$regex);
        $userProperty->setData($value);
        $this->getResourceManager()->update($id);
    }

    /**
     * @ApiDoc
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_ADMIN')")
     */
    public function roleAction(Request $request, User $id)
    {
        return $this->processOtherField($request, $id, 'role');
    }

    /**
     * @ApiDoc
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_USERNAME')")
     */
    public function usernameAction(Request $request, User $id)
    {
        return $this->processOtherField($request, $id, 'username');
    }

    /**
     * @ApiDoc
     */
    public function displaynameAction(Request $request, User $id)
    {
        return $this->processOtherField($request, $id, 'displayName');
    }

    /**
     * @ApiDoc
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordAction(Request $request, User $id)
    {
        return $this->processOtherField($request, $id, 'password');
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/disable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordDisableAction(User $id)
    {
        $id->setPasswordEnabled(0);
        $this->getResourceManager()->update($id);
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/enable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordEnableAction(Request $request, User $id)
    {
        $id->setPasswordEnabled(1);
        $this->getResourceManager()->update($id);
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/settable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordInitialAction(Request $request, User $id)
    {
        $id->setPasswordEnabled(2);
        $this->getResourceManager()->update($id);
    }

    private function processOtherField(Request $request, User $user, $field)
    {
        $form = $this->createEditForm($user);
        $form->submit(array($field => $request->getContent()), false);
        if(!$form->isValid())
            return $form->get($field);
        $this->getResourceManager()->update($user);
        return null;
    }

    protected function handleLink($type, $user, $link)
    {
        switch ($type) {
            case 'group':
                if (!$this->hasRole('ROLE_SCOPE_W_PROFILE_GROUPS')) {
                    throw new AccessDeniedException();
                }
                $group = $link->getData();
                if (!$group instanceof Group) {
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                }
                if (!$user->getGroups()->contains($group)) {
                    $user->addGroup($group);
                }
                break;
            default:
                throw new BadRequestHttpException('Invalid relationship (allowed: group)');
        }
    }

    protected function handleUnlink($type, $user, $link)
    {
        switch ($type) {
            case 'group':
                if (!$this->hasRole('ROLE_SCOPE_W_PROFILE_GROUPS')) {
                    throw new AccessDeniedException();
                }

                $group = $link->getData();
                if (!$group instanceof Group) {
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
        if ($action == 'get') {
            if ($this->hasRole('ROLE_SCOPE_R_PROFILE_EMAIL')) {
                $groups[] = 'admin_user_object_scope_email';
            }
        }

        return $groups;
    }

    private function hasRole($role)
    {
        if (($rm = $this->getResourceManager()) instanceof SecuredResourceManager) {
            /* @var $rm SecuredResourceManager */
            if (($ac = $rm->getAuthorizationChecker()) instanceof DefaultAuthorizationChecker) {
                /* @var $ac DefaultAuthorizationChecker */

                return $ac->hasRole($role);
            }
        }

        return false;
    }

    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        if ($this->hasRole('ROLE_SCOPE_W_PROFILE_ENABLED')) {
            $actions['Account enabled']['PATCH_enabled_true'] = 'Enable';
            $actions['Account enabled']['PATCH_enabled_false'] = 'Disable';
            $actions['Password authentication']['PATCH_passwordEnabled_0'] = 'Disable';
            $actions['Password authentication']['PATCH_passwordEnabled_1'] = 'Enable';
            $actions['Password authentication']['PATCH_passwordEnabled_2'] = 'Let user set initial password';
        }

        return $actions;
    }

    protected function handleBatch($action, $subjects)
    {
        switch ($action) {
            case 'PATCH_enabled_false':
            case 'PATCH_enabled_true':
                foreach ($subjects as $id => $exec) {
                    if ($exec) {
                        $user = $this->getResourceManager()->find($id);
                        if ($user->getRole() !== 'ROLE_SUPER_ADMIN') {
                            $user->setEnabled($action === 'PATCH_enabled_true');
                            $this->getResourceManager()->update($user);
                        }
                    }
                }
                break;
            default:
                parent::handleBatch($action, $subjects);
        }
    }
}
