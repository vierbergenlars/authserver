<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151118192009 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $propertyNs = $schema->createTable('PropertyNamespace');
        $propertyNs->addColumn('id', 'integer')->setAutoincrement(true);
        $propertyNs->addColumn('name', 'string');
        $propertyNs->addColumn('public_readable', 'boolean');
        $propertyNs->addColumn('public_writeable', 'boolean');
        $propertyNs->setPrimaryKey(array('id'));
        $propertyNs->addUniqueIndex(array('name'));

        $propertyNsReaders = $schema->createTable('propertynamespace_oauthclient_readers');
        $propertyNsReaders->addColumn('propertynamespace_id', 'integer');
        $propertyNsReaders->addColumn('client_id', 'integer');
        $propertyNsReaders->setPrimaryKey(array('propertynamespace_id', 'client_id'));
        $propertyNsReaders->addForeignKeyConstraint($propertyNs, array('propertynamespace_id'), array('id'), array('onDelete'=>'cascade'));
        $propertyNsReaders->addForeignKeyConstraint($schema->getTable('client'), array('client_id'), array('id'), array('onDelete'=>'cascade'));

        $propertyNsWriters = $schema->createTable('propertynamespace_oauthclient_writers');
        $propertyNsWriters->addColumn('propertynamespace_id', 'integer');
        $propertyNsWriters->addColumn('client_id', 'integer');
        $propertyNsWriters->setPrimaryKey(array('propertynamespace_id', 'client_id'));
        $propertyNsWriters->addForeignKeyConstraint($propertyNs, array('propertynamespace_id'), array('id'), array('onDelete'=>'cascade'));
        $propertyNsWriters->addForeignKeyConstraint($schema->getTable('client'), array('client_id'), array('id'), array('onDelete'=>'cascade'));

        $propertyData = $schema->createTable('PropertyData');
        $propertyData->addColumn('namespace_id', 'integer');
        $propertyData->addColumn('name', 'string');
        $propertyData->addColumn('user_id', 'integer');
        $propertyData->addColumn('data', 'blob');
        $propertyData->addColumn('content_type', 'string');
        $propertyData->setPrimaryKey(array('namespace_id', 'name', 'user_id'));
        $propertyData->addForeignKeyConstraint($propertyNs, array('namespace_id'), array('id'), array('onDelete'=>'cascade'));
        $propertyData->addForeignKeyConstraint($schema->getTable('auth_users'), array('user_id'), array('id'), array('onDelete'=>'cascade'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('propertynamespace_oauthclient_readers');
        $schema->dropTable('propertynamespace_oauthclient_writers');
        $schema->dropTable('PropertyData');
        $schema->dropTable('PropertyNamespace');
    }
}
