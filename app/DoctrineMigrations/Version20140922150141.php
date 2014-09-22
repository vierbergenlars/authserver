<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140922150141 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $group = $schema->getTable('auth_group');
        $group->getColumn('no_users')->setNotNull(true);
        $group->getColumn('no_groups')->setNotNull(true);
    }

    public function down(Schema $schema)
    {
        $group = $schema->getTable('auth_group');
        $group->getColumn('no_users')->setNotNull(false);
        $group->getColumn('no_groups')->setNotNull(false);
    }
}
