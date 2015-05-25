<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140915132715 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $accessToken = $schema->createTable('AccessToken');
        $accessToken->addColumn('id', 'integer')->setNotNull(true)->setAutoincrement(true);
        $accessToken->addColumn('client_id', 'integer')->setNotNull(true);
        $accessToken->addColumn('user_id', 'integer')->setNotNull(false);
        $accessToken->addColumn('token', 'string')->setLength(255)->setNotNull(true);
        $accessToken->addColumn('expires_at', 'integer')->setNotNull(false);
        $accessToken->addColumn('scope', 'string')->setLength(255)->setNotNull(false);
        $accessToken->setPrimaryKey(array('id'));
        $accessToken->addUniqueIndex(array('token'));

        $authCode = $schema->createTable('AuthCode');
        $authCode->addColumn('id', 'integer')->setNotNull(true)->setAutoincrement(true);
        $authCode->addColumn('client_id', 'integer')->setNotNull(true);
        $authCode->addColumn('user_id', 'integer')->setNotNull(false);
        $authCode->addColumn('token', 'string')->setLength(255)->setNotNull(true);
        $authCode->addColumn('redirect_uri', 'text')->setNotNull(true);
        $authCode->addColumn('expires_at', 'integer')->setNotNull(false);
        $authCode->addColumn('scope', 'string')->setLength(255)->setNotNull(false);
        $authCode->setPrimaryKey(array('id'));
        $authCode->addUniqueIndex(array('token'));

        $client = $schema->createTable('Client');
        $client->addColumn('id', 'integer')->setNotNull(true)->setAutoincrement(true);
        $client->addColumn('random_id', 'string')->setLength(255)->setNotNull(true);
        $client->addColumn('redirect_uris', 'array')->setNotNull(true);
        $client->addColumn('secret', 'string')->setLength(255)->setNotNull(true);
        $client->addColumn('allowed_grant_types', 'array')->setNotNull(true);
        $client->setPrimaryKey(array('id'));

        $refreshToken = $schema->createTable('RefreshToken');
        $refreshToken->addColumn('id', 'integer')->setNotNull(true)->setAutoincrement(true);
        $refreshToken->addColumn('client_id', 'integer')->setNotNull(true);
        $refreshToken->addColumn('user_id', 'integer')->setNotNull(false);
        $refreshToken->addColumn('token', 'string')->setLength(255)->setNotNull(true);
        $refreshToken->addColumn('expires_at', 'integer')->setNotNull(false);
        $refreshToken->addColumn('scope', 'string')->setLength(255)->setNotNull(false);
        $refreshToken->setPrimaryKey(array('id'));
        $refreshToken->addUniqueIndex(array('token'));

        $users = $schema->createTable('auth_users');
        $users->addColumn('id', 'integer')->setNotNull(true)->setAutoincrement(true);
        $users->addColumn('username', 'string')->setLength(25)->setNotNull(true);
        $users->addColumn('password', 'string')->setLength(64)->setNotNull(true);
        $users->addColumn('email', 'string')->setLength(60)->setNotNull(true);
        $users->addColumn('roles', 'array')->setNotNull(true);
        $users->addColumn('is_active', 'boolean')->setNotNull(true);
        $users->setPrimaryKey(array('id'));
        $users->addUniqueIndex(array('username'));
        $users->addUniqueIndex(array('email'));

        foreach(array($accessToken, $authCode, $refreshToken) as $table) {
            $table->addForeignKeyConstraint($client, array('client_id'), array('id'));
            $table->addForeignKeyConstraint($users, array('user_id'), array('id'));
        }
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('AccessToken');
        $schema->dropTable('AuthCode');
        $schema->dropTable('Client');
        $schema->dropTable('RefreshToken');
        $schema->dropTable('auth_users');
    }
}
