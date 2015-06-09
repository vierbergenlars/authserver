<?php

namespace Admin\Entity;

use Admin\Security\LogEntryAuthorizationChecker;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;
use vierbergenlars\Bundle\RadRestBundle\Manager\ResourceManagerInterface;
use vierbergenlars\Bundle\RadRestBundle\Manager\SecuredResourceManager;
use vierbergenlars\Bundle\RadRestBundle\Pagination\PageDescriptionInterface;

class LogEntryRepositoryWrapper extends SecuredResourceManager implements ResourceManagerInterface
{
    /**
     * @var LogEntryRepository
     */
    private $logEntry;

    function __construct(LogEntryRepository $logEntry)
    {
        $this->logEntry = $logEntry;
    }

    /**
     * Gets the whole collection of objects
     *
     * @return PageDescriptionInterface
     */
    public function getPageDescription()
    {
        return new QueryBuilderPageDescription($this->logEntry->createQueryBuilder('l')->orderBy('l.loggedAt', 'DESC'));
    }

    /**
     * Finds the object by its database ID
     *
     * @param string|int $id
     *
     * @return object|null
     */
    public function find($id)
    {
        return $this->logEntry->find($id);
    }

    /**
     * Creates a blank instance of the managed object
     *
     * @return object
     */
    public function newInstance()
    {
        return null;
    }

    /**
     * Creates an object in the database
     *
     * @param object $object
     *
     * @throws \Exception if the object cannot be created
     * @return void
     */
    public function create($object)
    {
        throw new \Exception(__METHOD__.' is not implemented');
    }

    /**
     * Updates an object in the database
     *
     * @param object $object
     *
     * @throws \Exception if the object cannot be updated
     * @return void
     */
    public function update($object)
    {
        throw new \Exception(__METHOD__.' is not implemented');
    }

    /**
     * Deletes an object from the database
     *
     * @param object $object
     *
     * @throws \Exception if the object cannot be deleted
     * @return void
     */
    public function delete($object)
    {
        throw new \Exception(__METHOD__.' is not implemented');
    }

    public function getResourceManager()
    {
        return $this;
    }

    public function getAuthorizationChecker()
    {
        return new LogEntryAuthorizationChecker();
    }
}