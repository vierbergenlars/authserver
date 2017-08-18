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


use User\Form\EmailAddressType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class UserEmailTypeInterceptorExtension extends AbstractTypeExtension
{
    /**
     * @var EmailValidationSubscriber
     */
    private $emailValidationSubscriber;

    public function __construct(EmailValidationSubscriber $emailValidationSubscriber)
    {
        $this->emailValidationSubscriber = $emailValidationSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->get('email')->addEventSubscriber($this->emailValidationSubscriber);
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return EmailAddressType::class;
    }
}
