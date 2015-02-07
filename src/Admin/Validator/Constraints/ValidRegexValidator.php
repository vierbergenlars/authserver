<?php

namespace Admin\Validator\Constraints;

class ValidRegexValidator extends \Symfony\Component\Validator\ConstraintValidator
{
    public function validate($value, \Symfony\Component\Validator\Constraint $constraint) {
        if(@preg_match($value, '') === false) {
            $this->context->addViolation($constraint->message);
        }
    }    
}
