<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150810181526 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $oauthTables = array(
            $schema->getTable('AccessToken'),
            $schema->getTable('AuthCode'),
            $schema->getTable('RefreshToken'),
            $schema->getTable('user_oauthclient'),
        );
        $client = $schema->getTable('Client');
        $user = $schema->getTable('auth_users');
        foreach($oauthTables as $table) {
            /* @var $table Table */
            $this->dropFks($table);
            $table->addForeignKeyConstraint($client, array('client_id'), array('id'), array('onDelete'=>'CASCADE'), 'fk_'.$table->getName().'_client');
            $table->addForeignKeyConstraint($user, array('user_id'), array('id'), array('onDelete'=>'CASCADE'), 'fk_'.$table->getName().'_user');
        }
    }

    private function dropFks(Table $table)
    {
        foreach($table->getForeignKeys() as $fk)
            $table->removeForeignKey($fk->getName());
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $oauthTables = array(
            $schema->getTable('AccessToken'),
            $schema->getTable('AuthCode'),
            $schema->getTable('RefreshToken'),
            $schema->getTable('user_oauthclient'),
        );
        $client = $schema->getTable('Client');
        $user = $schema->getTable('auth_users');
        foreach($oauthTables as $table) {
            /* @var $table Table */
            $this->dropFks($table);
            $table->addForeignKeyConstraint($client, array('client_id'), array('id'));
            $table->addForeignKeyConstraint($user, array('user_id'), array('id'));
        }
    }
}
