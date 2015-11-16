<?php

namespace Admin\Controller;

use App\Entity\OAuth\Client;
use App\Form\OAuth\ClientType;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Security("has_role('ROLE_ADMIN')")
 */
class OAuthClientController extends CRUDController
{
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

        return $this->view($this->paginate($queryBuilder, $request))
            ->setTemplateData(array('batch_form'=>$this->createBatchForm()->createView()));
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

        return $this->routeRedirectView('admin_oauth_client_gets');
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

        return $this->routeRedirectView('admin_oauth_client_get', array('client'=>$client->getId()), Codes::HTTP_NO_CONTENT);
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

        return $this->routeRedirectView('admin_oauth_client_get', array('client'=>$form->getData()->getId()));
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
        return $this->routeRedirectView('admin_oauth_client_gets', array(), Codes::HTTP_NO_CONTENT);
    }

    protected function getFormType()
    {
        return new ClientType();
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
        $actions['Pre approved']['PATCH_preApproved_true'] = 'Enable';
        $actions['Pre approved']['PATCH_preApproved_false'] = 'Disable';
        return $actions;
    }
}
