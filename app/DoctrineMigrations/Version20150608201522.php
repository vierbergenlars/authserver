<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150608201522 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $userProperties = $schema->getTable('user_properties');
        $userProperties->getColumn('id')->setNotnull(true);
        $userProperties->dropPrimaryKey();
        $userProperties->setPrimaryKey(array('id'));
        $userProperties->addUniqueIndex(array('user_id', 'property_id'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $userProperties = $schema->getTable('user_properties');
        $userProperties->getColumn('id')->setNotnull(false);
        $userProperties->dropPrimaryKey();
        $userProperties->setPrimaryKey(array('user_id', 'property_id'));
    }
}
