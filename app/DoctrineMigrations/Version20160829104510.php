<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160829104510 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema->getTable('email_message')->addColumn('priority', 'integer')->setDefault(1);
        $schema->getTable('email_queued_message')->addColumn('priority', 'integer')->setDefault(1);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('email_message')->dropColumn('priority');
        $schema->getTable('email_queued_message')->dropColumn('priority');

    }
}
