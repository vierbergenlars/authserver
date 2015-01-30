<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150130145739 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('auth_users')->addColumn('password_enabled', 'integer')->setDefault(1);

    }

    public function down(Schema $schema)
    {
        $schema->getTable('auth_users')->dropColumn('password_enabled');
    }
}
