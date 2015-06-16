<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150608201523 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->getTable('user_properties')
            ->getColumn('id')->setAutoincrement(true);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('user_properties')
            ->getColumn('id')->setAutoincrement(false);
    }
}
