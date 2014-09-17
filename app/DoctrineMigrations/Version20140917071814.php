<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140917071814 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $user = $schema->getTable('auth_users');
        $group = $schema->createTable('auth_group');
        $group->addColumn('id', 'integer');
        $group->addColumn('name', 'string');
        $group->addColumn('exportable', 'boolean');
        $group->setPrimaryKey(array('id'));
        $group->addUniqueIndex(array('name'));

        $group_user = $schema->createTable('group_user');
        $group_user->addColumn('group_id', 'integer');
        $group_user->addColumn('user_id', 'integer');
        $group_user->setPrimaryKey(array('group_id', 'user_id'));
        $group_user->addForeignKeyConstraint($group, array('group_id'), array('id'));
        $group_user->addForeignKeyConstraint($user, array('user_id'), array('id'));

        $group_group = $schema->createTable('group_group');
        $group_group->addColumn('group_source', 'integer');
        $group_group->addColumn('group_target', 'integer');
        $group_group->setPrimaryKey(array('group_source', 'group_target'));
        $group_group->addForeignKeyConstraint($group, array('group_source'), array('id'));
        $group_group->addForeignKeyConstraint($group, array('group_target'), array('id'));
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('group_group');
        $schema->dropTable('group_user');
        $schema->dropTable('auth_group');
    }
}
