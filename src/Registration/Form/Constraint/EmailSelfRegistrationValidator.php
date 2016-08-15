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

namespace Registration\Form\Constraint;

use App\Entity\EmailAddress;
use Doctrine\Common\Collections\Collection;
use Registration\RegistrationHandler\RegistrationRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailSelfRegistrationValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailSelfRegistration) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\EmailSelfRegistration');
        }

        if($value instanceof Collection)
            $value = $value->filter(function(EmailAddress $emailAddress) {
                return $emailAddress->isPrimary();
            })->first();
        if($value instanceof EmailAddress)
            $value = $value->getEmail();
        
        $rule = $constraint->registrationRules->getFirstRuleMatching($value);
        
        if(!$rule || !$rule->isSelfRegistration()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(EmailSelfRegistration::SELF_REGISTRATION_DENIED_ERROR)
                ->addViolation();
        }
    }
}
