<?php

namespace Admin\Controller;

use Admin\Entity\ApiKey;
use Admin\Form\ApiKeyType;
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
 * @Security("has_role('ROLE_SUPER_ADMIN')")
 */
class ApiKeyController extends CRUDController
{
    /**
     * @ApiDoc
     * @View
     * @Get(name="s")
     */
    public function cgetAction(Request $request) {
        $queryBuilder = $this->getEntityRepository()
            ->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC');

        return $this->view($this->paginate($queryBuilder, $request))->setTemplateData(array('batch_form'=>$this->createBatchForm()->createView()));
    }

    /**
     * @ApiDoc
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

        return $this->routeRedirectView('admin_apikey_gets');
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

        return $this->routeRedirectView('admin_apikey_get', array('apikey'=>$apikey->getId()), Codes::HTTP_NO_CONTENT);
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

        return $this->routeRedirectView('admin_apikey_get', array('apikey'=>$form->getData()->getId()));
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
        return $this->routeRedirectView('admin_apikey_gets', array(), Codes::HTTP_NO_CONTENT);
    }

    protected function getFormType()
    {
        return new ApiKeyType();
    }

    protected function createNewEntity()
    {
        return new ApiKey();
    }

    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository('AdminBundle:ApiKey');
    }
}
