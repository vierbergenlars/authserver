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
        $this->addSql('UPDATE auth_group SET no_users=0, no_groups=0');
    }

    public function down(Schema $schema)
    {

    }
}
