<?php

namespace App\Search;

class SearchValueException extends SearchException
{
    private $fieldname;
    private $value;
    private $whitelist;
    public function __construct($fieldname, $value, $whitelist)
    {
        parent::__construct(sprintf('Bad value "%s" for field "%s"; allowed: %s', $value, $fieldname, json_encode($whitelist)));
        $this->fieldname = $fieldname;
        $this->value = $value;
        $this->whitelist = $whitelist;
    }

    public function getFieldName()
    {
        return $this->fieldname;
    }

    public function getWhitelist()
    {
        return $this->whitelist;
    }

    public function getValue()
    {
        return $this->value;
    }
}
