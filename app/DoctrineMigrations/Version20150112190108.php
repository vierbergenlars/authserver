<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150112190108 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $result = $this->connection->query("SELECT id FROM auth_users WHERE guid IS NULL OR guid = id");
        
        $guidQuery = 'SELECT '.$this->platform->getGuidExpression();
        while(($id = $result->fetchColumn()) !== false) {
            $guid = $this->connection->query($guidQuery)->fetchColumn(0);
            $this->addSql('UPDATE auth_users SET guid=? WHERE id=?', array($guid, $id));
        }
    }

    public function down(Schema $schema)
    {

    }
}
