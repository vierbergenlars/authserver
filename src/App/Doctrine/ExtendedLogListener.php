<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

namespace App\Doctrine;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;

class ExtendedLogListener extends  LoggableListener
{
    private static function attachManyToManyLogEntry(AbstractLogEntry $logEntry, $fieldName, Collection $collection)
    {
        if($collection instanceof PersistentCollection) {
            $insertDiff = $collection->getInsertDiff();
            $deleteDiff = $collection->getDeleteDiff();
        } else {
            $insertDiff = $collection->toArray();
            $deleteDiff = array();
        }

        $logEntryData = $logEntry->getData();
        if(count($insertDiff) > 0) {
            $logEntryData[$fieldName]['+'] = array_map(function(Group $obj) {
                return $obj->getMigrateId();
            },$insertDiff);
        }
        if(count($deleteDiff) > 0) {
            $logEntryData[$fieldName]['-'] = array_map(function(Group $obj) {
                return $obj->getMigrateId();
            },$deleteDiff);
        }
        $logEntry->setData($logEntryData);
    }

    protected function prePersistLogEntry($logEntry, $object)
    {
        if ($object instanceof User) {
            self::attachManyToManyLogEntry($logEntry, 'groups', $object->getGroups());
        } elseif ($object instanceof Group) {
            self::attachManyToManyLogEntry($logEntry, 'groups', $object->getGroups());
        }
    }

    /**
     * Create a new Log instance
     *
     * @param string          $action
     * @param object          $object
     * @param LoggableAdapter $ea
     *
     * @see parent::createLogEntry() Copy and 3 lines commented out
     *
     * @return \Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry|null
     */
    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();

        // Filter embedded documents
        $ident = $meta->getIdentifier();
        if (empty($ident) || empty($ident[0])) {
            return;
        }

        if ($config = $this->getConfiguration($om, $meta->name)) {
            $logEntryClass = $this->getLogEntryClass($ea, $meta->name);
            $logEntryMeta = $om->getClassMetadata($logEntryClass);
            /** @var \Gedmo\Loggable\Entity\LogEntry $logEntry */
            $logEntry = $logEntryMeta->newInstance();

            $logEntry->setAction($action);
            $logEntry->setUsername($this->username);
            $logEntry->setObjectClass($meta->name);
            $logEntry->setLoggedAt();

            // check for the availability of the primary key
            $uow = $om->getUnitOfWork();
            if ($action === self::ACTION_CREATE && $ea->isPostInsertGenerator($meta)) {
                $this->pendingLogEntryInserts[spl_object_hash($object)] = $logEntry;
            } else {
                $logEntry->setObjectId($wrapped->getIdentifier());
            }
            $newValues = array();
            if ($action !== self::ACTION_REMOVE && isset($config['versioned'])) {
                $newValues = $this->getObjectChangeSetData($ea, $object, $logEntry);
                $logEntry->setData($newValues);
            }

//            if($action === self::ACTION_UPDATE && 0 === count($newValues)) {
//                return null;
//            }

            $version = 1;
            if ($action !== self::ACTION_CREATE) {
                $version = $ea->getNewVersion($logEntryMeta, $object);
                if (empty($version)) {
                    // was versioned later
                    $version = 1;
                }
            }
            $logEntry->setVersion($version);

            $this->prePersistLogEntry($logEntry, $object);

            $om->persist($logEntry);
            $uow->computeChangeSet($logEntryMeta, $logEntry);

            return $logEntry;
        }

        return null;
    }
}