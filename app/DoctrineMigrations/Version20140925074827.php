<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140925074827 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $user_oauthClient = $schema->createTable('user_oauthclient');
        $user_oauthClient->addColumn('user_id', 'integer');
        $user_oauthClient->addColumn('client_id', 'integer');
        $user_oauthClient->setPrimaryKey(array('user_id', 'client_id'));

        $user = $schema->getTable('auth_users');
        $client = $schema->getTable('client');

        $user_oauthClient->addForeignKeyConstraint($user, array('user_id'), array('id'));
        $user_oauthClient->addForeignKeyConstraint($client, array('client_id'), array('id'));
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('user_oauthclient');
    }
}
