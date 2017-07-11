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

namespace Registration\RegistrationHandler;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;

class RegistrationRule
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
     * @var bool
     */
    private $selfRegistration;
    /**
     * @var bool
     */
    private $autoActivate;

    /**
     * RegistrationRule constructor.
     *
     * @param string|null $emailRegex
     * @param string|null $emailDomain
     * @param bool $selfRegistration
     * @param bool $autoActivate
     */
    public function __construct($emailRegex, $emailDomain, $selfRegistration, $autoActivate)
    {

        $this->emailRegex = $emailRegex;
        $this->emailDomain = $emailDomain;
        $this->selfRegistration = $selfRegistration;
        $this->autoActivate = $autoActivate;
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
     * @return boolean
     */
    public function isSelfRegistration()
    {
        return $this->selfRegistration;
    }

    /**
     * @return boolean
     */
    public function isAutoActivate()
    {
        return $this->autoActivate;
    }
}

