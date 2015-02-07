<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150207122734 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('properties')->getColumn('display_name')->setNotnull(true);
    }

    public function down(Schema $schema)
    {
        $schema->getTable('properties')->getColumn('display_name')->setNotnull(false);
    }
}
