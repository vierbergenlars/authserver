<?php
namespace OAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class AuthorizeController extends Controller
{

    /**
     * @Route("/authorize", name="_authorize_validate")
     *
     * @method ({"GET"})
     *         @Template("OAuthBundle:Authorize:authorize.html.twig")
     */
    public function validateAuthorizeAction(Request $request)
    {
        $server = $this->get('oauth2.server');

        if (!$server->validateAuthorizeRequest($this->get('oauth2.request'), $this->get('oauth2.response'))) {
            return $server->getResponse();
        }

        // Get descriptions for scopes if available
        $scopes = array();
        $scopeStorage = $this->get('oauth2.storage.scope');
        foreach (explode(' ', $this->get('oauth2.request')->query->get('scope')) as $scope) {
            $scopes[] = $scope;
        }

        $qs = array_intersect_key($this->get('oauth2.request')->query->all(), array_flip(explode(' ', 'response_type client_id redirect_uri scope state nonce')));

        return array(
            'qs' => $qs,
            'scopes' => $scopes,
            'request' => $request
        );
    }

    /**
     * @Route("/authorize", name="_authorize_handle")
     *
     * @method ({"POST"})
     */
    public function handleAuthorizeAction()
    {
        $server = $this->get('oauth2.server');

        return $server->handleAuthorizeRequest($this->get('oauth2.request'), $this->get('oauth2.response'), true);
    }
}
