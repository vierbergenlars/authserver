<?php
namespace OAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class UserinfoController extends Controller
{

    /**
     * @Route("/userinfo", name="_userinfo")
     */
    public function userinfoAction()
    {
        $server = $this->get('oauth2.server');

        return $server->handleUserInfoRequest($this->get('oauth2.request'), $this->get('oauth2.response'));
    }
}
