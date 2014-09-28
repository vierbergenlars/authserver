<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140928093756 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $apiKey = $schema->createTable('ApiKey');
        $apiKey->addColumn('id', 'integer');
        $apiKey->addColumn('scopes', 'simple_array');
        $apiKey->addColumn('secret', 'string');
        $apiKey->addColumn('name', 'string');
        $apiKey->setPrimaryKey(array('id'));
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('ApiKey');
    }
}
