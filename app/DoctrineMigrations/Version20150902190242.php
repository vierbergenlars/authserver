<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150902190242 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $client = $schema->getTable('Client');
        $client->addColumn('groupRestriction_id', 'integer')->setNotnull(false);
        $client->addForeignKeyConstraint($schema->getTable('auth_group'), array('groupRestriction_id'), array('id'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('Client')->dropColumn('groupRestriction_id');
    }
}
