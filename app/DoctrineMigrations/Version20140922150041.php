<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140922150041 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $false = $this->connection->getDatabasePlatform()->convertBooleans(false);
        $this->addSql('UPDATE auth_group SET no_users='.$false.', no_groups='.$false);
    }

    public function down(Schema $schema)
    {

    }
}
