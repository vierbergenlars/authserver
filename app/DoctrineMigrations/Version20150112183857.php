<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150112183857 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('auth_users')->addColumn('guid', 'guid')->setNotnull(false);
    }

    public function down(Schema $schema)
    {
        $schema->getTable('auth_users')->dropColumn('guid');
    }
}
