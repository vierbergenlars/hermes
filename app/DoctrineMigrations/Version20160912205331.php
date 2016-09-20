<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160912205331 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $template = $schema->getTable('email_template');
        $template->addColumn('sender_id', 'integer')->setNotnull(false);

        $schema->getTable('email_message')->getColumn('sender_id')->setNotnull(false);

        $template->addForeignKeyConstraint($schema->getTable('email_address'), ['sender_id'], ['id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->getTable('email_template')->dropColumn('sender_id');
        $schema->getTable('email_message')->getColumn('sender_id')->setNotnull(true);
    }
}
