<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150903065521 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->getTable('Client')->addColumn('maxScopes', 'simple_array')->setNotnull(false);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('Client')->dropColumn('maxScopes');
    }
}
