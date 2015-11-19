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
use App\Entity\Property\PropertyNamespace;
use App\Form\Property\PropertyNamespaceType;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Patch;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class PropertyNamespaceController extends CRUDController
{
    /**
     * @ApiDoc
     * @View
     * @Get(name="s")
     */
    public function cgetAction(Request $request) {
        $queryBuilder = $this->getEntityRepository()
            ->createQueryBuilder('ns')
            ->orderBy('ns.id', 'DESC');

        return $this->view($this->paginate($queryBuilder, $request))->setTemplateData(array('batch_form'=>$this->createBatchForm()->createView()));
    }

    /**
     * @ApiDoc
     * @View
     */
    public function getAction(PropertyNamespace $ns)
    {
        return $this->view($ns)
            ->setTemplateData(array(
                'properties' => $this->getEntityManager()
                    ->createQueryBuilder()
                    ->select('COUNT(p.name) AS c', 'p.name')
                    ->from('AppBundle:Property\\PropertyData', 'p')
                    ->where('p.namespace = :ns')
                    ->groupBy('p.name')
                    ->getQuery()
                    ->setParameter('ns', $ns)
                    ->getArrayResult()
            ));
    }

    /**
     * @Post
     */
    public function batchAction(Request $request)
    {
        $this->handleBatch($request);

        return $this->routeRedirectView('admin_property_namespace_gets');
    }

    /**
     * @View
     */
    public function editAction(PropertyNamespace $ns)
    {
        return $this->createEditForm($ns);
    }

    /**
     * @View(template="AdminBundle:PropertyNamespace:edit.html.twig")
     */
    public function putAction(Request $request, PropertyNamespace $ns)
    {
        $form = $this->createEditForm($ns);
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_property_namespace_get', array('ns'=>$ns->getId()), Codes::HTTP_NO_CONTENT);
    }

    /**
     * @View
     */
    public function newAction()
    {
        return $this->createCreateForm();
    }

    /**
     * @View(template="AdminBundle:PropertyNamespace:new.html.twig")
     */
    public function postAction(Request $request)
    {
        $form = $this->createCreateForm();
        $form->handleRequest($request);

        if(!$form->isValid())
            return $form;

        $this->getEntityManager()->persist($form->getData());
        $this->getEntityManager()->flush();

        return $this->routeRedirectView('admin_property_namespace_get', array('ns'=>$form->getData()->getId()));
    }

    /**
     * @View
     */
    public function removeAction(PropertyNamespace $ns)
    {
        return $this->createDeleteForm();
    }

    /**
     * @View(template="AdminBundle:PropertyNamespace:remove.html.twig")
     */
    public function deleteAction(Request $request, PropertyNamespace $ns)
    {
        $ret = $this->handleDelete($request, $ns);
        if ($ret)
            return $ret;
        return $this->routeRedirectView('admin_property_namespace_gets', array(), Codes::HTTP_NO_CONTENT);
    }

    protected function getFormType()
    {
        return new PropertyNamespaceType();
    }

    protected function createNewEntity()
    {
        return new PropertyNamespace();
    }

    protected function getEntityRepository()
    {
        return $this->getEntityManager()->getRepository('AppBundle:Property\\PropertyNamespace');
    }
}
