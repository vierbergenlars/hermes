<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160427143418 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $emailTemplate = $schema->createTable('email_template');
        $emailTemplate->addColumn('id', 'integer')->setAutoincrement(true);
        $emailTemplate->addColumn('name', 'string');
        $emailTemplate->addUniqueIndex(['name']);
        $emailTemplate->setPrimaryKey(['id']);

        $localizedEmailTemplate = $schema->createTable('localized_email_template');
        $localizedEmailTemplate->addColumn('template_id', 'integer');
        $localizedEmailTemplate->addColumn('locale', 'string')->setLength(2);
        $localizedEmailTemplate->addColumn('subject', 'text');
        $localizedEmailTemplate->addColumn('body', 'text');
        $localizedEmailTemplate->setPrimaryKey(['template_id', 'locale']);

        $localizedEmailTemplate->addForeignKeyConstraint($emailTemplate,['template_id'], ['id'], ['onDelete' => 'cascade']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('email_template');
        $schema->dropTable('localized_email_template');
    }
}
