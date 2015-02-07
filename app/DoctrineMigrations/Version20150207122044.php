<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150207122044 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('properties')->addColumn('display_name', 'string')->setLength(255)->setNotnull(false);
        $schema->getTable('properties')->addColumn('validation_regex', 'text')->setDefault('/^.*$/');
    }

    public function down(Schema $schema)
    {
        $schema->getTable('properties')->dropColumn('display_name');
        $schema->getTable('properties')->dropColumn('validation_regex');
    }
}
