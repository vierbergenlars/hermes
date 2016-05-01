<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160506234358 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $localizedTemplate = $schema->getTable('localized_email_template');
        $localizedTemplate->addUniqueIndex(['template_id', 'locale']);

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $localizedTemplate = $schema->getTable('localized_email_template');
        $localizedTemplate->dropPrimaryKey();
        foreach($localizedTemplate->getIndexes() as $index) {
            if($index->isUnique()&& $index->spansColumns(['template_id', 'locale']))
                $localizedTemplate->dropIndex($index->getName());
        }
    }
}
