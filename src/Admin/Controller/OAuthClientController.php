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

use App\Entity\OAuth\Client;
use App\Form\OAuth\ClientType;
use FOS\OAuthServerBundle\Util\Random;
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

class OAuthClientController extends CRUDController
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
    public function cgetAction(Request $request)
    {
        $queryBuilder = $this->getEntityRepository()
            ->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        if($request->query->has('q'))
            $queryBuilder->where('c.name LIKE :name')
                ->setParameter('name', '%'.$request->query->get('q').'%');

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
    public function getAction(Client $client)
    {
        return $client;
    }

    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $this->handleBatch($request);

        return $this->routeRedirectView('admin_oauth_client_gets', [], Response::HTTP_NO_CONTENT);
    }

    /**
     * @View
     */
    public function editAction(Client $client)
    {
        return $this->createEditForm($client);
    }

    /**
     * @View(template="AdminBundle:OAuthClient:edit.html.twig")
     */
    public function putAction(Request $request, Client $client)
    {
        $form = $this->createEditForm($client);
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_oauth_client_get', array('client'=>$client->getId()), Response::HTTP_NO_CONTENT);
    }

    /**
     * @View
     */
    public function newAction()
    {
        return $this->createCreateForm();
    }

    /**
     * @View(template="AdminBundle:OAuthClient:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->createCreateForm();
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->persist($form->getData());
        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_oauth_client_get', array('client'=>$form->getData()->getId()), Response::HTTP_CREATED);
    }

    /**
     * @View
     */
    public function removeAction(Client $client)
    {
        return $this->createDeleteForm();
    }

    /**
     * @View(template="AdminBundle:OAuthClient:remove.html.twig")
     */
    public function deleteAction(Request $request, Client $client)
    {
        $ret = $this->handleDelete($request, $client);
        if ($ret)
            return $ret;
        return $this->routeRedirectView('admin_oauth_client_gets', array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * @NoRoute
     * @View
     */
    public function rotateFormAction(Client $client)
    {
        return $this->getRotateForm($client);
    }

    /**
     * @Post
     * @View
     */
    public function rotateAction(Request $request, Client $client)
    {
        $form = $this->getRotateForm($client);

        $form->handleRequest($request);

        if(!$form->isValid()) {
            $this->addFlash('danger', 'OAuth client secret could not be regenerated.');
        } else {
            $client->setSecret(Random::generateToken());
            $this->getEntityManager()->flush();
        }

        return $this->routeRedirectView('admin_oauth_client_get', array('client' => $client->getId()), Response::HTTP_NO_CONTENT);
    }

    protected function getFormType()
    {
        return ClientType::class;
    }

    protected function getEntityRepository()
    {
        return $this->getDoctrine()->getRepository('AppBundle:OAuth\\Client');
    }

    protected function createNewEntity()
    {
        return new Client();
    }

    protected function getBatchActions()
    {
        $actions = parent::getBatchActions();
        $actions['Pre approved']['Enable'] = 'PATCH_preApproved_true';
        $actions['Pre approved']['Disable'] = 'PATCH_preApproved_false';
        return $actions;
    }

    private function getRotateForm(Client $client)
    {
        return $this->createFormBuilder()
            ->setMethod('POST')
            ->setAction($this->generateUrl('admin_oauth_client_rotate', array('client'=>$client->getId())))
            ->add('rotate', SubmitType::class, array(
                'label' => 'Regenerate secret',
                'button_class' => 'danger btn-xs',
            ))
            ->getForm();
    }
}
