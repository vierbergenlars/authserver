<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140916130833 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $client = $schema->getTable('Client');

        $client->addColumn('name', 'string')->setLength(255)->setNotNull(false);
        $client->addColumn('preApproved', 'boolean')->setNotNull(false);
        $client->addUniqueIndex(array('name'));
    }

    public function postUp(Schema $schema)
    {
        $this->connection->beginTransaction();
        $this->connection->query('UPDATE Client SET name = random_id, preApproved = 0');
        $this->connection->commit();
    }

    public function down(Schema $schema)
    {
        $client = $schema->getTable('Client');
        $client->dropColumn('name');
        $client->dropColumn('preApproved');
    }
}
