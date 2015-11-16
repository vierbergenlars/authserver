<?php

namespace Admin\Controller;

use Admin\Form\EventListener\UserTypeLocalFlagsEventListener;
use App\Entity\Group;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @ParamConverter("user",options={"mapping":{"user":"guid"}})
 */
class UserController extends CRUDController
{
    /**
     * @ApiDoc
     * @View(serializerGroups={"admin_user_list", "list"})
     * @Get(name="s")
     */
    public function cgetAction(Request $request) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:User')
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        $searchForm = $this->createSearchForm()->handleRequest($request);

        if($searchForm->isValid()) {
            if (!$searchForm->get('username')->isEmpty())
                $queryBuilder->andWhere('u.username LIKE :username')
                    ->setParameter('username', $searchForm->get('username')->getData());
            if (!$searchForm->get('name')->isEmpty())
                $queryBuilder->andWhere('u.displayName LIKE :displayName')
                    ->setParameter('displayName', $searchForm->get('name')->getData());
            if (!$searchForm->get('email')->isEmpty())
                $queryBuilder->leftJoin('u.emailAddresses', 'e')
                    ->andWhere('e.email LIKE :email')
                    ->setParameter('email', $searchForm->get('email')->getData());
            foreach (array_merge($searchForm->get('is')->getData(), $searchForm->get('is')->getExtraData()) as $value) {
                switch ($value) {
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
                }
            }
        }
        return $this->view($this->paginate($queryBuilder, $request))
            ->setTemplateData(array(
                'batch_form' => $this->createBatchForm()->createView(),
                'search_form' => $searchForm->createView(),
            ));
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
        $repository = $this->getEntityRepository();

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

        $this->handleBatch($request);

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
        return $this->createEditForm($user);
    }

    /**
     * @View(template="AdminBundle:User:edit.html.twig")
     */
    public function putAction(Request $request, User $user)
    {
        $form = $this->createEditForm($user);
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
        return $this->createCreateForm();
    }

    /**
     * @View(template="AdminBundle:User:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->createCreateForm();
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
        $ret = $this->handleDelete($request, $user);
        if($ret)
            return $ret;
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
    public function roleAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'role');
    }

    /**
     * @ApiDoc
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_USERNAME')")
     */
    public function usernameAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'username');
    }

    /**
     * @ApiDoc
     */
    public function displaynameAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'displayName');
    }

    /**
     * @ApiDoc
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'password');
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/disable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordDisableAction(User $user)
    {
        $user->setPasswordEnabled(0);
        $this->getEntityManager()->flush();
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/enable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     * @param User $user
     */
    public function passwordEnableAction(User $user)
    {
        $user->setPasswordEnabled(1);
        $this->getEntityManager()->flush();
    }

    /**
     * @ApiDoc
     * @Patch("/{id}/password/settable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordInitialAction(User $user)
    {
        $user->setPasswordEnabled(2);
        $this->getEntityManager()->flush();
    }

    private function processOtherField(Request $request, User $user, $field)
    {
        $form = $this->createEditForm($user);
        $form->submit(array($field => $request->getContent()), false);
        if(!$form->isValid())
            return $form->get($field);

        $this->getEntityManager()->flush();
        return null;
    }


    /**
     * @return UserType
     */
    protected function getFormType()
    {
        return new UserType(new UserTypeLocalFlagsEventListener($this->get('security.authorization_checker')));
    }

    protected function createNewEntity()
    {
        return new User();
    }

    /**
     * @return EntityRepository
     */
    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository('AppBundle:User');
    }

    /**
     * @return FormInterface
     */
    private function createSearchForm()
    {
        $ff = $this->get('form.factory');
        /* @var $ff FormFactoryInterface */
        $isForm = $ff->createNamedBuilder('is', 'form', null, array(
            'allow_extra_fields' => true,
            'label' => false,
            'required' => false,
        ))
            ->add('admin', 'choice', array(
                'choices' => array(
                    'admin', 'superadmin', 'user'
                ),
                'expanded' => true,
                'required' => false,
            ))
            ->add('enabled', 'choice', array(
                'choices' => array(
                    'enabled', 'disabled'
                ),
                'expanded' => true,
                'required' => false,
            ));
        return $ff->createNamedBuilder('q', 'form', null, array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ))
            ->setMethod('GET')
            ->add('username', 'text', array(
                'required' => false,
            ))
            ->add('name', 'text', array(
                'required' => false,
            ))
            ->add('email', 'text', array(
                'required' => false,
            ))
            ->add($isForm)
            ->add('search', 'submit')
            ->getForm();
    }
}
