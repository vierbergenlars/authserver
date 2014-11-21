<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141118155826 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $emailAddress = $schema->createTable('EmailAddress');
        $emailAddress->addColumn('id', 'integer');
        $emailAddress->addColumn('email', 'string');
        $emailAddress->addColumn('verified', 'boolean');
        $emailAddress->addColumn('verificationCode', 'string')->setNotNull(false);
        $emailAddress->addColumn('primary_mail', 'boolean');
        $emailAddress->addColumn('user_id', 'integer');
        $emailAddress->setPrimaryKey(array('id'));
        $emailAddress->addUniqueIndex(array('email'));

        $user = $schema->getTable('auth_users');
        $user->getColumn('email')->setNotNull(false);

        $emailAddress->addForeignKeyConstraint($user, array('user_id'), array('id'));
    }

    public function down(Schema $schema)
    {
        $this->throwIrreversibleMigrationException('This migration is irreversible, because email addresses would be lost');
    }
}
