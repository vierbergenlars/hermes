<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160424191423 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $emailAddresses = $schema->createTable('email_address');
        $emailAddresses->addColumn('id', 'integer')->setAutoincrement(true);
        $emailAddresses->addColumn('name', 'string');
        $emailAddresses->addColumn('email', 'string');
        $emailAddresses->addColumn('authCode', 'string')->setNotnull(false)->setLength(64);

        $emailAddresses->setPrimaryKey(['id']);
        $emailAddresses->addUniqueIndex(['email']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('email_address');
    }
}
