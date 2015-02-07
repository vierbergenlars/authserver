<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150207122539 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE properties SET display_name = name');
    }

    public function down(Schema $schema)
    {

    }
}
