<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150903065737 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $scopes = array(
            'profile:username' => 'profile:username',
            'profile:realname' => 'profile:realname',
            'profile:groups'   => 'profile:groups',
            'group:join'       => 'group:join',
            'group:leave'      => 'group:leave',
        );
        $this->addSql('UPDATE Client SET maxScopes = "'.implode(',', $scopes).'"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
