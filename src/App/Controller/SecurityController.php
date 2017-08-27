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

namespace App\Controller;

use App\AppEvents;
use App\Event\TemplateEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class SecurityController extends Controller
{
    public function loginAction(Request $request)
    {
        if($this->getUser())
            return $this->redirectToRoute('user_profile');

        $session = $request->getSession();

        $error = null;
        // get the login error if there is one
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::AUTHENTICATION_ERROR);
        }

        if ($session->has('_security.target_path')) {
            if (false !== strpos($session->get('_security.target_path'), $this->generateUrl('fos_oauth_server_authorize'))) {
                $session->set('_fos_oauth_server.ensure_logout', true);
            }
        }

        $eventDispatcher = $this->get('event_dispatcher');

        $templateEvent = new TemplateEvent(null, [
            'last_username' => $session->get(Security::LAST_USERNAME),
            'error' => $error
        ]);

        $bodyTemplateEvent = clone $templateEvent;
        $eventDispatcher->dispatch(AppEvents::LOGIN_VIEW_BODY, $bodyTemplateEvent);
        $footerTemplateEvent = clone $templateEvent;
        $eventDispatcher->dispatch(AppEvents::LOGIN_VIEW_FOOTER, $footerTemplateEvent);

        return $this->render('AppBundle:Security:login.html.twig', array(
            'bodyTemplateEvent' => $bodyTemplateEvent,
            'footerTemplateEvent' => $footerTemplateEvent,
        ));
    }
}
