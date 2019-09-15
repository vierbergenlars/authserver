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

/**
 * Created by PhpStorm.
 * User: lars
 * Date: 24/08/17
 * Time: 16:39
 */
namespace App\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

class TemplateEvent extends GenericEvent implements \Countable, TemplateEventInterface
{
    use TemplateEventTrait;

    public function __construct($subject = null, array $attributes = [])
    {
        parent::__construct($subject, $attributes);
        if ($subject !== null) {
            $this->setGlobalData([
                'subject' => $subject
            ]);
        }
    }
}
