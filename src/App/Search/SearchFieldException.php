<?php

namespace App\Search;

class SearchFieldException extends \RuntimeException
{
    private $fieldname;
    private $whitelist;
    public function __construct($fieldname, $whitelist) {
        parent::__construct(sprintf('Bad field name "%s"; allowed: %s', $fieldname, json_encode($whitelist)));
        $this->fieldname = $fieldname;
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
}