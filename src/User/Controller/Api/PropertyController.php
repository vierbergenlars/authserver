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

namespace User\Controller\Api;

use App\Controller\PaginateTrait;
use App\Entity\OAuth\AccessToken;
use App\Entity\Property\PropertyData;
use App\Entity\Property\PropertyNamespace;
use Doctrine\ORM\EntityManager;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @ParamConverter("ns", options={"mapping":{"ns":"name"}})
 */
class PropertyController extends BaseController implements ClassResourceInterface
{
    use PaginateTrait;

    /**
     * @View(serializerGroups={"api_list", "list"})
     */
    public function cgetAction(Request $request, PropertyNamespace $ns)
    {
        $this->denyAccessUnlessGrantedScope('property:read');
        if(!$this->mayReadNamespace($ns))
            throw $this->createAccessDeniedException('Client not allowed to read this namespace.');

        $properties = $this->getDoctrine()
            ->getRepository('AppBundle:Property\\PropertyData')
            ->findBy(array(
                'namespace' => $ns,
                'user' => $this->getUser(),
            ));
        return $this->paginate($properties, $request);
    }

    /**
     * @Get("/properties/{ns}/{property}")
     */
    public function getAction(PropertyNamespace $ns, $property)
    {
        $this->denyAccessUnlessGrantedScope('property:read');
        if(!$this->mayReadNamespace($ns))
            throw $this->createAccessDeniedException('Client not allowed to read this namespace.');

        $propertyData = $this->getDoctrine()->getRepository('AppBundle:Property\\PropertyData')
            ->find(array(
                'namespace' => $ns,
                'user' => $this->getUser(),
                'name' => $property,
            ));
        if(!$propertyData)
            throw $this->createNotFoundException('Property does not exist');
        /* @var $propertyData PropertyData */

        return new StreamedResponse(function() use($propertyData) {
            fpassthru($propertyData->getData());
        }, 200, array(
            'Content-Type' => $propertyData->getContentType()
        ));
    }

    /**
     * @Put("/properties/{ns}/{property}")
     */
    public function putAction(Request $request, PropertyNamespace $ns, $property)
    {
        $this->denyAccessUnlessGrantedScope('property:write');
        if(!$this->mayWriteNamespace($ns))
            throw $this->createAccessDeniedException('Client not allowed to write this namespace.');

        $propertyData = $this->getDoctrine()->getRepository('AppBundle:Property\\PropertyData')
            ->find(array(
                'namespace' => $ns,
                'user' => $this->getUser(),
                'name' => $property,
            ));
        $em = $this->getDoctrine()->getManagerForClass('AppBundle:Property\\PropertyData');

        if(!$propertyData) {
            $propertyData = new PropertyData();
            $propertyData->setNamespace($ns);
            $propertyData->setUser($this->getUser());
            $propertyData->setName($property);

            $em->persist($propertyData);
        }

        $propertyData->setData($request->getContent());
        $propertyData->setContentType($request->headers->get('Content-Type'));

        $em->flush();
    }

    /**
     * @Delete("/properties/{ns}/{property}")
     */
    public function deleteAction(PropertyNamespace $ns, $property)
    {
        $this->denyAccessUnlessGrantedScope('property:write');
        if(!$this->mayWriteNamespace($ns))
            throw $this->createAccessDeniedException('Client not allowed to write this namespace.');

        $propertyData = $this->getDoctrine()->getRepository('AppBundle:Property\\PropertyData')
            ->find(array(
                'namespace' => $ns,
                'user' => $this->getUser(),
                'name' => $property,
            ));
        $em = $this->getDoctrine()->getManagerForClass('AppBundle:Property\\PropertyData');
        $em->remove($propertyData);
        $em->flush();
    }

    /**
     * Get the OAuth client that is performing the request
     * @return \FOS\OAuthServerBundle\Model\ClientInterface|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getOAuthClient()
    {
        $token = $this->getToken();
        if(!$token instanceof OAuthToken)
            return null;
        $em = $this->getDoctrine()
            ->getManagerForClass('AppBundle:OAuth\\AccessToken');
        /* @var $em EntityManager */
        $accessToken = $em->createQueryBuilder()
            ->select('at,c')
            ->from('App\\Entity\\OAuth\\AccessToken', 'at')
            ->join('at.client', 'c')
            ->where('at.token = :token')
            ->setParameter('token', $token->getToken())
            ->getQuery()
            ->getOneOrNullResult();
        if(!$accessToken)
            return null;
        /* @var $accessToken AccessToken */
        return $accessToken->getClient();
    }

    /**
     * Checks if the requester may read the namespace
     * @param PropertyNamespace $ns
     * @return bool
     */
    private function mayReadNamespace(PropertyNamespace $ns)
    {
        if($ns->isPublicReadable())
            return true;
        return $ns->getReaders()->contains($this->getOAuthClient());
    }

    /**
     * Checks if the requester may write the namespace
     * @param PropertyNamespace $ns
     * @return bool
     */
    private function mayWriteNamespace(PropertyNamespace $ns)
    {
        if($ns->isPublicWriteable())
            return true;
        return $ns->getWriters()->contains($this->getOAuthClient());
    }
}
