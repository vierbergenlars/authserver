<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141118202106 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->fetchColumn("SELECT COUNT(*) FROM auth_users WHERE email IS NOT NULL") > 0,
            'Not all users have been migrated to the new email address storage.'
        );

        $user = $schema->getTable('auth_users');
        $user->dropColumn('email');
    }

    public function down(Schema $schema)
    {
        $user = $schema->getTable('auth_users');
        $user->addColumn('email', 'string')->setNotNull(false);
    }
}
