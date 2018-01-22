<?php
namespace OAuthBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\Request;
use OAuthBundle\Event\OAuthEvent;
use OAuthBundle\Form\AuthorizeFormType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\OAuth\Client;
use Symfony\Component\VarDumper\VarDumper;

class AuthorizeController extends Controller
{

    /**
     * @Get("/auth", name="_authorize_validate")
     * @Post("/auth", name="_authorize_handle")
     * @View("OAuthBundle:Authorize:authorize.html.twig")
     */
    public function validateAuthorizeAction(Request $request)
    {
        $server = $this->get('oauth2.server');

        $request->query->set('scope', $this->get('app.oauth.scopes')
            ->getReachableScopes($request->query->get('scope', '')));
        if (!$server->validateAuthorizeRequest($this->get('oauth2.request'), $this->get('oauth2.response'))) {
            return $server->getResponse();
        }

        $form = $this->createForm(AuthorizeFormType::class, $request->query->all());
        $form->handleRequest($request);
        $client = $this->getClient($request->query->get('client_id'));

        $event = $this->container->get('event_dispatcher')->dispatch(OAuthEvent::PRE_AUTHORIZATION_PROCESS, new OAuthEvent($this->getUser(), $client));
        if ($form->isValid()) {
            $event = $this->container->get('event_dispatcher')->dispatch(OAuthEvent::POST_AUTHORIZATION_PROCESS, new OAuthEvent($this->getUser(), $client, $request->request->has('accepted')));
        }
        if ($form->isValid() || $event->isAuthorizedClient()) {
            $request->query->add($form->getData());
            return $server->handleAuthorizeRequest($this->get('oauth2.request'), $this->get('oauth2.response'), $event->isAuthorizedClient(), $this->getUser()
                ->getGuid());
        }

        return array(
            'client' => $client,
            'form' => $form->createView()
        );
    }

    /**
     *
     * @param string $clientId
     * @throws NotFoundHttpException
     * @return Client
     */
    private function getClient($clientId)
    {
        $client = $this->get('oauth2.storage.client_credentials')->getClient($clientId);
        if (!$client) {
            throw new NotFoundHttpException('Client not found.');
        }
        return $client;
    }
}
