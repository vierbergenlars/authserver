<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150608172233 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $logTable = $schema->createTable('ext_log_entries');
        $logTable->addColumn('id', 'integer')->setAutoincrement(true);
        $logTable->addColumn('action', 'string')->setLength(8);
        $logTable->addColumn('logged_at', 'datetime');
        $logTable->addColumn('object_id', 'string')->setLength(64)->setNotnull(false);
        $logTable->addColumn('object_class', 'string')->setLength(255);
        $logTable->addColumn('version', 'integer');
        $logTable->addColumn('data', 'array')->setNotnull(false);
        $logTable->addColumn('username', 'string')->setLength(255)->setNotnull(false);

        $logTable->setPrimaryKey(array('id'));
        $logTable->addIndex(array('object_class'));
        $logTable->addIndex(array('logged_at'));
        $logTable->addIndex(array('username'));
        $logTable->addIndex(array('object_id', 'object_class', 'version'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('ext_log_entries');
    }
}
