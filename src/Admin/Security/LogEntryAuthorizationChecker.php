<?php

namespace Admin\Security;

use vierbergenlars\Bundle\RadRestBundle\Security\AuthorizationCheckerInterface;

class LogEntryAuthorizationChecker implements AuthorizationCheckerInterface
{

    /**
     * Checks authorization to list all objects
     *
     * @return bool
     */
    public function mayList()
    {
        return true;
    }

    /**
     * Checks authorization to create this new object
     *
     * @param object $object
     *
     * @return bool
     */
    public function mayCreate($object)
    {
        return false;
    }

    /**
     * Checks authorization to view a specific object
     *
     * @param object $object
     *
     * @return bool
     */
    public function mayView($object)
    {
        return true;
    }

    /**
     * Checks authorization to edit a specific object
     *
     * @param object $object
     *
     * @return bool
     */
    public function mayEdit($object)
    {
        return false;
    }

    /**
     * Checks authorization to delete a specific object
     *
     * @param object $object
     *
     * @return bool
     */
    public function mayDelete($object)
    {
        return false;
}}