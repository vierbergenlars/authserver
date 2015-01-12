<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150112155701 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE auth_users SET display_name=username WHERE display_name IS NULL');
        $this->addSql('UPDATE auth_group SET display_name=name WHERE display_name IS NULL');
    }

    public function down(Schema $schema)
    {

    }
}
