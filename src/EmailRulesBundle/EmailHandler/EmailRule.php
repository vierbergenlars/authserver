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

namespace EmailRulesBundle\EmailHandler;

class EmailRule
{
    /**
     * @var string
     */
    private $emailRegex;
    /**
     * @var string
     */
    private $emailDomain;

    /**
     * @var string
     */
    private $role;

    /**
     * @var string[]
     */
    private $groups;

    /**
     * @var bool
     */
    private $reject;

    /**
     * RegistrationRule constructor.
     *
     * @param string|null $emailRegex
     * @param string|null $emailDomain
     * @param string|null $role
     * @param string[] $groups
     * @param bool $reject
     */
    public function __construct($emailRegex, $emailDomain, $role, $groups, $reject)
    {

        $this->emailRegex = $emailRegex;
        $this->emailDomain = $emailDomain;
        $this->role = $role;
        $this->groups = $groups;
        $this->reject = $reject;
    }

    public function match($emailAddress)
    {
        return $this->matchRegex($emailAddress) && $this->matchDomain($emailAddress);
    }

    private function matchRegex($emailAddress)
    {
        if($this->emailRegex === null)
            return true;

        return preg_match($this->emailRegex, $emailAddress);
    }

    private function matchDomain($emailAddress)
    {
        if($this->emailDomain === null)
            return true;
        $host = substr($emailAddress, strrpos($emailAddress, '@') + 1);

        return $host === $this->emailDomain;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return boolean
     */
    public function isReject()
    {
        return $this->reject;
    }
}

