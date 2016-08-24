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
