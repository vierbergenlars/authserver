<?php

namespace User\Controller\Api;

use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{
    protected function isOAuth()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return false;
        }

        return $token instanceof OAuthToken;
    }

    protected function isGrantedScope($scope)
    {
        if(!$this->isOAuth())
            return true;
        return $this->isGranted('ROLE_'.strtoupper($scope));
    }

    protected function denyAccessUnlessGrantedScope($scope)
    {
        if(!$this->isGrantedScope($scope))
            throw $this->createAccessDeniedException('OAuth scope '.$scope.' is required to access this resource.');
    }
}