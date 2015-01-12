<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150112204154 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('auth_users')->getColumn('guid')->setNotnull(true);
    }

    public function down(Schema $schema)
    {
        $schema->getTable('auth_users')->getColumn('guid')->setNotnull(true);
    }
}
