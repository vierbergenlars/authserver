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

use Admin\Entity\ApiKey;
use Admin\Form\ApiKeyType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\NoRoute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyController extends CRUDController
{
    /**
     * @View
     * @NoRoute
     */
    public function dashboardAction(Request $request)
    {
        return parent::dashboardAction($request);
    }

    /**
     * @View
     * @Get(name="s")
     */
    public function cgetAction(Request $request) {
        $queryBuilder = $this->getEntityRepository()
            ->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC');

        $data = $this->paginate($queryBuilder, $request);

        return $this->view($data)->setTemplateData(array(
            'batch_form' => $this->createBatchForm()
                ->createView(),
            'display_list_event' => $this->getDisplayListEvent($data)
        ));
    }

    /**
     * @View
     */
    public function getAction(ApiKey $apikey)
    {
        return $apikey;
    }

    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $this->handleBatch($request);

        return $this->routeRedirectView('admin_apikey_gets', [], Response::HTTP_NO_CONTENT);
    }

    /**
     * @View
     */
    public function editAction(ApiKey $apikey)
    {
        return $this->createEditForm($apikey);
    }

    /**
     * @View(template="AdminBundle:ApiKey:edit.html.twig")
     */
    public function putAction(Request $request, ApiKey $apikey)
    {
        $form = $this->createEditForm($apikey);
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_apikey_get', array('apikey'=>$apikey->getId()), Response::HTTP_NO_CONTENT);
    }

    /**
     * @View
     */
    public function newAction()
    {
        return $this->createCreateForm();
    }

    /**
     * @View(template="AdminBundle:ApiKey:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->createCreateForm();
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->persist($form->getData());
        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_apikey_get', array('apikey'=>$form->getData()->getId()), Response::HTTP_CREATED);
    }

    /**
     * @View
     */
    public function removeAction(ApiKey $apikey)
    {
        return $this->createDeleteForm();
    }

    /**
     * @View(template="AdminBundle:ApiKey:remove.html.twig")
     */
    public function deleteAction(Request $request, ApiKey $apikey)
    {
        $ret = $this->handleDelete($request, $apikey);
        if ($ret)
            return $ret;
        return $this->routeRedirectView('admin_apikey_gets', array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * @NoRoute
     * @View
     */
    public function rotateFormAction(ApiKey $apikey)
    {
        return $this->getRotateForm($apikey);
    }

    /**
     * @Post
     * @View
     */
    public function rotateAction(Request $request, ApiKey $apikey)
    {
        $form = $this->getRotateForm($apikey);

        $form->handleRequest($request);

        if(!$form->isValid()) {
            $this->addFlash('danger', 'API key secret could not be regenerated.');
        } else {
            $apikey->regenerateSecret();
            $this->getEntityManager()->flush();
        }

        return $this->routeRedirectView('admin_apikey_get', array('apikey' => $apikey->getId()), Response::HTTP_NO_CONTENT);
    }

    protected function getFormType()
    {
        return ApiKeyType::class;
    }

    protected function createNewEntity()
    {
        return new ApiKey();
    }

    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository('AdminBundle:ApiKey');
    }

    private function getRotateForm(ApiKey $apiKey)
    {
        return $this->createFormBuilder()
            ->setMethod('POST')
            ->setAction($this->generateUrl('admin_apikey_rotate', array('apikey'=>$apiKey->getId())))
            ->add('rotate', SubmitType::class, array(
                'label' => 'Regenerate key',
                'button_class' => 'danger btn-xs',
            ))
            ->getForm();
    }
}
