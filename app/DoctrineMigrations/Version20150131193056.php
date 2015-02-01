<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150131193056 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('auth_users')->getColumn('password')->setNotnull(false);
    }

    public function down(Schema $schema)
    {
        $schema->getTable('auth_users')->getColumn('password')->setNotnull(true);
    }
}
