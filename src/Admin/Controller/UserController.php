<?php

namespace Admin\Controller;

use Admin\Form\EventListener\UserTypeLocalFlagsEventListener;
use App\Entity\Group;
use App\Entity\User;
use App\Entity\UserProperty;
use App\Form\UserType;
use App\Search\SearchFieldException;
use App\Search\SearchGrammar;
use App\Search\SearchValueException;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @ParamConverter("user",options={"mapping":{"user":"guid"}})
 */
class UserController extends BaseController
{
    /**
     * @ApiDoc
     * @View(serializerGroups={"admin_user_list", "list"})
     * @Get(name="s")
     */
    public function cgetAction(Request $request) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:User')
            ->createQueryBuilder('u');

        $query = $request->query->get('q', array());
        if (is_string($query)) {
            $parser = new SearchGrammar();
            $blocks = $parser->parse($query);
        } elseif (is_array($query)) {
            $blocks = array();
            foreach ($query as $name=>$value) {
                if(is_array($value)) {
                    foreach($value as $v) {
                        $blocks[] = array(
                            'name'=>$name,
                            'value'=>$v,
                        );
                    }
                } else {
                    $blocks[] = array(
                        'name' => $name,
                        'value' => $value,
                    );
                }
            }
        } else {
            throw new BadRequestHttpException;
        }

        foreach($blocks as $block) {
            $likeOrEq = (strpos($block['value'], '*') !== false?'LIKE':'=');
            $queryValue = str_replace('*', '%',$block['value']);
            switch($block['name']) {
                case 'username':
                    $queryBuilder->andWhere('u.username '.$likeOrEq.' :username')
                        ->setParameter('username', $queryValue);
                    break;
                case 'name':
                    $queryBuilder->andWhere('u.displayName '.$likeOrEq.' :displayname')
                        ->setParameter('displayname', $queryValue);
                    break;
                case 'email':
                    $queryBuilder->leftJoin('u.emails', 'e')
                        ->andWhere('e.email '.$likeOrEq.' :email')
                        ->setParameter('email', $queryValue);
                    break;
                case 'is':
                    switch($block['value']) {
                        case 'admin':
                            $queryBuilder->andWhere('u.role IN(\'ROLE_ADMIN\', \'ROLE_SUPER_ADMIN\')');
                            break;
                        case 'su':
                        case 'superadmin':
                        case 'super_admin':
                            $queryBuilder->andWhere('u.role = \'ROLE_SUPER_ADMIN\'');
                            break;
                        case 'user':
                            $queryBuilder->andWhere('u.role IN(\'ROLE_USER\',\'ROLE_AUDIT\')');
                            break;
                        case 'enabled':
                            $queryBuilder->andWhere('u.enabled = true');
                            break;
                        case 'disabled':
                            $queryBuilder->andWhere('u.enabled = false');
                            break;
                        default:
                            throw new SearchValueException($block['name'], $block['value'], array('admin', 'su', 'superadmin', 'super_admin', 'user', 'enabled', 'disabled'));
                    }
                    break;
                default:
                    throw new SearchFieldException($block['name'], array('username', 'email', 'is'));
            }
        }

        return $this->view($this->paginate($queryBuilder, $request))->setTemplateData(array('batch_form'=>$this->createBatchForm()->createView()));
    }

    /**
     * @ApiDoc
     * @View
     */
    public function getAction(User $user)
    {
        $view = $this->view($user);

        $serializationGroups = array('admin_user_object', 'object');
        if($this->isGranted('ROLE_SCOPE_R_PROFILE_EMAIL'))
            $serializationGroups[] = 'admin_user_object_scope_email';

        $view->getSerializationContext()->setGroups($serializationGroups);

        return $view;
    }

    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:User');

        // Do not allow to edit super admins in batch mode
        $form = $this->createBatchForm();
        $form->handleRequest($request);
        foreach ($form->get('subjects')->getData() as $id => $exec) {
            if ($exec) {
                $user = $repository->find($id);
                if ($user->getRole() === 'ROLE_SUPER_ADMIN') {
                    throw $this->createAccessDeniedException();
                }
            }
        }

        $this->handleBatch($request, $repository);

        return $this->routeRedirectView('admin_user_gets');
    }

    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        if ($this->isGranted('ROLE_SCOPE_W_PROFILE_ENABLED')) {
            $actions['Account enabled']['PATCH_enabled_true'] = 'Enable';
            $actions['Account enabled']['PATCH_enabled_false'] = 'Disable';
            $actions['Password authentication']['PATCH_passwordEnabled_0'] = 'Disable';
            $actions['Password authentication']['PATCH_passwordEnabled_1'] = 'Enable';
            $actions['Password authentication']['PATCH_passwordEnabled_2'] = 'Let user set initial password';
        }

        return $actions;
    }

    /**
     * @View
     */
    public function editAction(User $user)
    {
        return $this->createEditForm($this->createFormType(), $user);
    }

    /**
     * @View(template="AdminBundle:User:edit.html.twig")
     */
    public function putAction(Request $request, User $user)
    {
        $form = $this->createEditForm($this->createFormType(), $user);
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_user_get', array('user'=>$user->getGuid()), Codes::HTTP_NO_CONTENT);
    }

    /**
     * @View
     */
    public function newAction()
    {
        return $this->createCreateForm($this->createFormType(), $this->createEntity());
    }

    /**
     * @View(template="AdminBundle:User:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->createCreateForm($this->createFormType(), $this->createEntity());
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->persist($form->getData());
        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_user_get', array('user'=>$form->getData()->getGuid()));
    }

    /**
     * @View
     */
    public function removeAction(User $user)
    {
        return $this->createDeleteForm();
    }

    /**
     * @View(template="AdminBundle:User:remove.html.twig")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm();
        $form->handleRequest($request);
        if($this->isGranted('ROLE_API'))
            $form->submit(null);
        if(!$form->isValid())
            return $form;
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_user_gets', array(), Codes::HTTP_NO_CONTENT);
    }

    public function linkAction(Request $request, User $user)
    {
        $this->handleLink($request, array(
            'group' => function($group) use ($user) {
                if(!$group instanceof Group)
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                if(!$user->getGroups()->contains($group))
                    $user->addGroup($group);
            }
        ));
        $this->getEntityManager()->flush();
    }

    public function unlinkAction(Request $request, User $user)
    {
        $this->handleLink($request, array(
            'group' => function($group) use ($user) {
                if(!$group instanceof Group)
                    throw new BadRequestHttpException('Subresource of wrong type (expected: group)');
                $user->removeGroup($group);
            }
        ));
        $this->getEntityManager()->flush();
    }

    /**
     * @ApiDoc
     * @ParamConverter("u",options={"mapping":{"u":"guid"}})
     * @Security("(has_role('ROLE_SCOPE_W_PROFILE_ENABLED') and u.getRole() not in ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']) or has_role('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')")
     */
    public function enableAction(User $u)
    {
        $u->setEnabled(true);
        $this->getEntityManager()->flush();
    }

    /**
     * @ApiDoc
     * @ParamConverter("u",options={"mapping":{"u":"guid"}})
     * @Security("(has_role('ROLE_SCOPE_W_PROFILE_ENABLED') and u.getRole() not in ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']) or has_role('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')")
     */
    public function disableAction(User $u)
    {
        $u->setEnabled(false);
        $this->getEntityManager()->flush();
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
        $this->getEntityManager()->flush();
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/enable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     * @param User $id
     */
    public function passwordEnableAction(User $id)
    {
        $id->setPasswordEnabled(1);
        $this->getEntityManager()->flush();
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/settable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordInitialAction(User $id)
    {
        $id->setPasswordEnabled(2);
        $this->getEntityManager()->flush();
    }

    private function processOtherField(Request $request, User $user, $field)
    {
        $form = $this->createEditForm($this->createFormType(), $user);
        $form->submit(array($field => $request->getContent()), false);
        if(!$form->isValid())
            return $form->get($field);

        $this->getEntityManager()->flush();
        return null;
    }


    /**
     * @return UserType
     */
    private function createFormType()
    {
        return new UserType(new UserTypeLocalFlagsEventListener($this->get('security.authorization_checker')));
    }

    private function createEntity()
    {
        return new User();
    }

}
