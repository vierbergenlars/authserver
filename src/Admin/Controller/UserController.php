<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Admin\Controller;

use Admin\Form\EventListener\UserTypeLocalFlagsEventListener;
use App\Entity\Group;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @ParamConverter("user",options={"mapping":{"user":"guid"}})
 */
class UserController extends CRUDController
{
    /**
     * @View
     * @Get(name="s")
     */
    public function cgetAction(Request $request) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('AppBundle:User')
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        $listFilter = $this->dispatchFilter($this->createSearchFormBuilder());

        $searchForm = $listFilter->getSearchForm();
        $queryBuilder->addCriteria($listFilter->getCriteria());

        if ($searchForm->isValid()) {
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
                    case 'audit':
                        $queryBuilder->andWhere('u.role = \'ROLE_AUDIT\'');
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

        $data = $this->paginate($queryBuilder, $request);
        $view = $this->view($data)->setTemplateData(array(
            'batch_form' => $this->createBatchForm()
                ->createView(),
            'search_form' => $searchForm->createView(),
            'display_list_event' => $this->getDisplayListEvent($data)
        ));
        $view->getContext()->setGroups([
            'admin_user_list',
            'list'
        ]);
        return $view;
    }

    /**
     * @View
     */
    public function getAction(User $user)
    {
        $view = $this->view($user);

        $serializationGroups = array('admin_user_object', 'object');
        if($this->isGranted('ROLE_SCOPE_R_PROFILE_EMAIL'))
            $serializationGroups[] = 'admin_user_object_scope_email';

        $view->getContext()->setGroups($serializationGroups);

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

        return $this->routeRedirectView('admin_user_gets', [], Response::HTTP_NO_CONTENT);
    }

    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        if ($this->isGranted('ROLE_SCOPE_W_PROFILE_ENABLED')) {
            $actions['Account enabled']['Enable'] = 'PATCH_enabled_true';
            $actions['Account enabled']['Disable'] = 'PATCH_enabled_false';
            $actions['Password authentication']['Disable'] = 'PATCH_passwordEnabled_0';
            $actions['Password authentication']['Enable'] = 'PATCH_passwordEnabled_1';
            $actions['Password authentication']['Let user set initial password'] = 'PATCH_passwordEnabled_2';
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

        return $this->routeRedirectView('admin_user_get', array('user'=>$user->getGuid()), Response::HTTP_NO_CONTENT);
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

        return $this->routeRedirectView('admin_user_get', array('user'=>$form->getData()->getGuid()), Response::HTTP_CREATED);
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
        return $this->routeRedirectView('admin_user_gets', array(), Response::HTTP_NO_CONTENT);
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
     * @ParamConverter("u",options={"mapping":{"u":"guid"}})
     * @Security("(has_role('ROLE_SCOPE_W_PROFILE_ENABLED') and u.getRole() not in ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']) or has_role('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')")
     */
    public function enableAction(User $u)
    {
        $u->setEnabled(true);
        $this->getEntityManager()->flush();
    }

    /**
     * @ParamConverter("u",options={"mapping":{"u":"guid"}})
     * @Security("(has_role('ROLE_SCOPE_W_PROFILE_ENABLED') and u.getRole() not in ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']) or has_role('ROLE_SCOPE_W_PROFILE_ENABLED_ADMIN')")
     */
    public function disableAction(User $u)
    {
        $u->setEnabled(false);
        $this->getEntityManager()->flush();
    }

    /**
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_ADMIN')")
     */
    public function roleAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'role');
    }

    /**
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_USERNAME')")
     */
    public function usernameAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'username');
    }

    public function displaynameAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'displayName');
    }

    /**
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordAction(Request $request, User $user)
    {
        return $this->processOtherField($request, $user, 'password');
    }

    /**
     * @Patch("/{id}/password/disable")
     * @Security("has_role('ROLE_SCOPE_W_PROFILE_CRED')")
     */
    public function passwordDisableAction(User $user)
    {
        $user->setPasswordEnabled(0);
        $this->getEntityManager()->flush();
    }

    /**
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
        return UserType::class;
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
     *
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createSearchFormBuilder()
    {
        $ff = $this->get('form.factory');
        /* @var $ff FormFactoryInterface */
        $isForm = $ff->createNamedBuilder('is', FormType::class, null, array(
            'allow_extra_fields' => true,
            'label' => false,
            'required' => false,
        ))
            ->add('admin', ChoiceType::class, array(
                'choices' => array(
                    'Admins' => 'admin',
                    'Super admins' => 'superadmin',
                    'Audit' => 'audit',
                    'Users' => 'user'
                ),
                'expanded' => true,
                'required' => false,
            ))
            ->add('enabled', ChoiceType::class, array(
                'choices' => array(
                    'Yes' => 'enabled',
                    'No' => 'disabled',
                ),
                'expanded' => true,
                'required' => false,
            ));
        return $ff->createNamedBuilder('q', FormType::class, null, array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ))
            ->setMethod('GET')
            ->add('username', TextType::class, array(
                'required' => false,
            ))
            ->add('name', TextType::class, array(
                'required' => false,
            ))
            ->add('email', TextType::class, array(
                'required' => false,
            ))
            ->add($isForm);
    }

}
