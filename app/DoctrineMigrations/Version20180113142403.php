<?php
namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180113142403 extends AbstractMigration
{

    /**
     *
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $client = $schema->getTable('client');

        $refreshToken = $schema->createTable('oauth_refresh_token');
        $refreshToken->addColumn('token', 'string')->setLength(40);
        $refreshToken->addColumn('scope', 'string')
            ->setLength(50)
            ->setNotnull(false);
        $refreshToken->addColumn('client_id', 'integer')->setNotnull(false);
        $refreshToken->addColumn('user_id', 'string')->setNotnull(false);
        $refreshToken->addColumn('expires', 'datetime');
        $refreshToken->setPrimaryKey([
            'token'
        ]);
        $refreshToken->addForeignKeyConstraint($client, [
            'client_id'
        ], [
            'id'
        ]);

        $accessToken = $schema->createTable('oauth_access_token');
        $accessToken->addColumn('token', 'string')->setLength(40);
        $accessToken->addColumn('scope', 'string')
            ->setLength(50)
            ->setNotnull(false);
        $accessToken->addColumn('client_id', 'integer')->setNotnull(false);
        $accessToken->addColumn('user_id', 'string')->setNotnull(false);
        $accessToken->addColumn('expires', 'datetime');
        $accessToken->setPrimaryKey([
            'token'
        ]);
        $accessToken->addForeignKeyConstraint($client, [
            'client_id'
        ], [
            'id'
        ]);

        $authorizationCode = $schema->createTable('oauth_authorization_code');
        $authorizationCode->addColumn('code', 'string')->setLength(40);
        $authorizationCode->addColumn('expires', 'datetime');
        $authorizationCode->addColumn('user_id', 'string')->setNotnull(false);
        $authorizationCode->addColumn('redirect_uri', 'simple_array');
        $authorizationCode->addColumn('scope', 'string')->setNotnull(false);
        $authorizationCode->addColumn('id_token', 'text')->setNotnull(false);
        $authorizationCode->addColumn('client_id', 'integer')->setNotnull(false);
        $authorizationCode->setPrimaryKey([
            'code'
        ]);
        $authorizationCode->addForeignKeyConstraint($client, [
            'client_id'
        ], [
            'id'
        ]);
    }

    /**
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('oauth_refresh_token');
        $schema->dropTable('oauth_access_token');
        $schema->dropTable('oauth_authorization_code');
    }
}
