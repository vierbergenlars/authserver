<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150112160341 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('auth_users')->getColumn('display_name')->setNotnull(true);
        $schema->getTable('auth_group')->getColumn('display_name')->setNotnull(true);
    }

    public function down(Schema $schema)
    {
        $schema->getTable('auth_users')->getColumn('display_name')->setNotnull(false);
        $schema->getTable('auth_group')->getColumn('display_name')->setNotnull(false);
    }
}
