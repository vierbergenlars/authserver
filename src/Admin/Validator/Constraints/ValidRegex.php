<?php

namespace Admin\Validator\Constraints;

class ValidRegex extends \Symfony\Component\Validator\Constraint {
    public $message = 'This regular expression is not valid';
}

