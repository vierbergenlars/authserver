<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace EmailRulesBundle\Form;


use EmailRulesBundle\EmailHandler\EmailRules;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EmailValidationSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailRules
     */
    private $emailRules;

    public function __construct(EmailRules $emailRules)
    {

        $this->emailRules = $emailRules;
    }

    public static function getSubscribedEvents()
    {
        return [FormEvents::POST_SUBMIT => 'onPostSubmit'];
    }

    public function onPostSubmit(FormEvent $event)
    {
        $emailAddress = $event->getData();
        $matchedRule = $this->emailRules->getFirstRuleMatching($emailAddress);

        if($matchedRule && $matchedRule->isReject())
            $event->getForm()->addError(new FormError('This email address is blacklisted: '.$matchedRule->getRejectMessage()));
    }
}
