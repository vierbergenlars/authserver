<?php

namespace User\SerializationHelper\Api;

class JoinableLeaveableGroups
{
    private $joinable;
    private $leaveable;

    public function __construct($joinable, $leaveable)
    {
        $this->joinable  = $joinable;
        $this->leaveable = $leaveable;
    }
}