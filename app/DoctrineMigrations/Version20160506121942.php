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
class Version20160506121942 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $emailMessage = $schema->createTable('email_message');
        $emailMessage->addColumn('id', 'integer')->setAutoincrement(true);
        $emailMessage->addColumn('sender_id', 'integer');
        $emailMessage->addColumn('template_id', 'integer');
        $emailMessage->addColumn('template_data', 'json_array')->setNotnull(false);
        $emailMessage->addColumn('transport_id', 'integer')->setNotnull(false);
        $emailMessage->addColumn('scheduled_send_time', 'datetime');
        $emailMessage->addColumn('sent_time', 'datetime')->setNotnull(false);

        $emailMessage->setPrimaryKey(['id']);
        $emailMessage->addForeignKeyConstraint($schema->getTable('email_address'), ['sender_id'], ['id']);
        $emailMessage->addForeignKeyConstraint($schema->getTable('email_template'), ['template_id'], ['id']);
        $emailMessage->addForeignKeyConstraint($schema->getTable('email_transport'), ['transport_id'], ['id']);

        $emailRecipient = $schema->createTable('email_recipient');
        $emailRecipient->addColumn('id', 'integer')->setAutoincrement(true);
        $emailRecipient->addColumn('message_id', 'integer');
        $emailRecipient->addColumn('recipient_type', 'string');
        $emailRecipient->addColumn('name', 'string')->setNotnull(false);
        $emailRecipient->addColumn('emailaddress', 'string')->setNotnull(false);
        $emailRecipient->addColumn('user_id', 'guid')->setNotnull(false);
        $emailRecipient->addColumn('group_name', 'string')->setNotnull(false);

        $emailRecipient->setPrimaryKey(['id']);
        $emailRecipient->addForeignKeyConstraint($emailMessage, ['message_id'], ['id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('email_message');
        $schema->dropTable('email_recipient');
    }
}
