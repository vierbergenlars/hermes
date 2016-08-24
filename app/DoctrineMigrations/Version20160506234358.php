<?php
/**
 * Hermes, an HTTP-based templated mail sender for transactional and mass mailing.
 *
 * Copyright (C) 2016  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
