<?php

namespace Registration\Form\Constraint;
use Registration\RegistrationHandler\RegistrationRule;
use Registration\RegistrationHandler\RegistrationRules;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class EmailSelfRegistration extends Constraint
{
    const SELF_REGISTRATION_DENIED_ERROR = '765D660A-C069-4B61-B22B-C1EF7C8322B7';

    protected static $errorNames = array(
        self::SELF_REGISTRATION_DENIED_ERROR => 'SELF_REGISTRATION_DENIED_ERROR',
    );

    public $message = 'Self registration is not possible with this email address.';

    /**
     * @var RegistrationRules
     */
    public $registrationRules;

    public function getDefaultOption()
    {
        return 'registrationRules';
    }

    public function getRequiredOptions()
    {
        return array('registrationRules');
    }
}
