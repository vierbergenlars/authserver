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

        // Add Grant Types
        $server->addGrantType($this->get('oauth2.grant_type.client_credentials'));
        $server->addGrantType($this->get('oauth2.grant_type.authorization_code'));
        $server->addGrantType($this->get('oauth2.grant_type.refresh_token'));
        $server->addGrantType($this->get('oauth2.grant_type.user_credentials'));

        return $server->handleUserInfoRequest($this->get('oauth2.request'), $this->get('oauth2.response'));
    }
}
