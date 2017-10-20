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
namespace Registration\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;

class RegistrationHandleEvent extends Event
{

    /**
     *
     * @var FormInterface
     */
    private $form;

    /**
     *
     * @var boolean
     */
    private $succeeded;

    public function __construct(FormInterface $form)
    {
        $this->form = $form;
        $this->succeeded = false;
    }

    /**
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     *
     * @return boolean
     */
    public function isSucceeded()
    {
        return $this->succeeded;
    }

    /**
     *
     * @param boolean $succeeded
     */
    public function setSucceeded($succeeded)
    {
        $this->succeeded = $succeeded;
    }
}

