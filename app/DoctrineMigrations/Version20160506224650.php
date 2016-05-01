<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160506224650 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $localizedTemplate = $schema->getTable('localized_email_template');
        $localizedTemplate->dropPrimaryKey();
        $localizedTemplate->addColumn('id', 'integer')->setColumnDefinition('INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $localizedTemplate = $schema->getTable('localized_email_template');
        $localizedTemplate->dropColumn('id');
        $localizedTemplate->setPrimaryKey(['template_id', 'locale']);
    }
}
