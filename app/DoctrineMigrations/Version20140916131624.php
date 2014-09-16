<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140916131624 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $client = $schema->getTable('Client');
        $client->getColumn('name')->setNotNull(true);
        $client->getColumn('preApproved')->setNotNull(true);
    }

    public function down(Schema $schema)
    {
        $client = $schema->getTable('Client');
        $client->getColumn('name')->setNotNull(false);
        $client->getColumn('preApproved')->setNotNull(false);
    }
}
