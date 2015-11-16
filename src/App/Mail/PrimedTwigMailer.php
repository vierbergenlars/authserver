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

namespace App\Mail;

use App\Entity\User;
use App\Entity\EmailAddress;

/**
 * Sends emails with a specific template with twigmailer
 */
class PrimedTwigMailer
{
    private $mailer;
    private $template;

    /**
     *
     * @param TwigMailer                     $mailer
     * @param string|\Twig_TemplateInterface $template
     */
    public function __construct(TwigMailer $mailer, $template)
    {
        $this->mailer = $mailer;
        $this->template = $mailer->getTemplate($template);
    }

    /**
     * Sends a message to a recipient, with the template rendered with the passed data.
     *
     * @param  User|EmailAddress|string $recipient The recipient of the message.
     *                                             If an {@link User} is passed, its primary email address is used.
     *                                             If a {@link EmailAddress} is passed, the email will only be sent when the address is verified
     *                                             If a string is passed, the email will always be sent to the given address.
     * @param  array|mixed              $data      The data to render the template with.
     *                                             If an array is passed, it is taken to be a map of twig variables to values.
     *                                             If something else is passed, the twig variable 'data' is assigned to this value.
     * @return boolean
     */
    public function sendMessage($recipient, $data)
    {
        if ($recipient instanceof User) {
            $recipient = $recipient->getPrimaryEmailAddress();
        }
        if ($recipient instanceof EmailAddress) {
            if (!$recipient->isVerified()) {
                return false;
            }
            $recipient = $recipient->getEmail();
        }
        if (!$recipient) {
            return false;
        }
        if (!is_array($data)) {
            $data = array('data' => $data);
        }

        return $this->mailer->sendMessage($this->template, $data, $recipient);
    }

}
