<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150608200144 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $query = $this->connection->executeQuery("SELECT user_id,property_id FROM user_properties WHERE id IS NULL");
        $i = 0;
        while($row = $query->fetch()) {
            $this->addSql("UPDATE user_properties SET id = ".++$i." WHERE user_id = ".$row['user_id']." AND property_id = ".$row['property_id']);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
