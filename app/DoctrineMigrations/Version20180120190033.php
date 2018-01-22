<?php
namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180120190033 extends AbstractMigration
{

    /**
     *
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $accessToken = $schema->getTable('oauth_access_token');
        $accessToken->dropColumn('scope');
        $accessToken->addColumn('scope', 'text')->setNotnull(false);
        $this->addSql('TRUNCATE TABLE oauth_access_token');

        $refreshToken = $schema->getTable('oauth_refresh_token');
        $refreshToken->dropColumn('scope');
        $refreshToken->addColumn('scope', 'text')->setNotnull(false);
        $this->addSql('TRUNCATE TABLE oauth_refresh_token');

        $authorizationCode = $schema->getTable('oauth_authorization_code');
        $authorizationCode->dropColumn('scope');
        $authorizationCode->addColumn('scope', 'text')->setNotnull(false);
        $this->addSql('TRUNCATE TABLE oauth_authorization_code');
    }

    /**
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // Nothing to do
    }
}
