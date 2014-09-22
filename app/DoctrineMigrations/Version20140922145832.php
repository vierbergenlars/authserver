<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140922145832 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $group = $schema->getTable('auth_group');
        $group->addColumn('no_users', 'boolean')->setNotNull(false);
        $group->addColumn('no_groups', 'boolean')->setNotNull(false);
    }

    public function down(Schema $schema)
    {
        $group = $schema->getTable('auth_group');
        $group->dropColumn('no_users', 'boolean');
        $group->dropColumn('no_groups', 'boolean');
    }
}
