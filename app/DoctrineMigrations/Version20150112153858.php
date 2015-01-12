<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150112153858 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('auth_users')->addColumn('display_name', 'string')->setNotnull(false);
        $schema->getTable('auth_group')->addColumn('display_name', 'string')->setNotnull(false);
    }

    public function down(Schema $schema)
    {
        $schema->getTable('auth_users')->dropColumn('display_name');
        $schema->getTable('auth_group')->dropColumn('display_name');
    }
}
