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
class Version20160519201721 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $queuedMessage = $schema->createTable('email_queued_message');
        $queuedMessage->addColumn('id', 'integer')->setAutoincrement(true);
        $queuedMessage->addColumn('source_recipient_id', 'integer');
        $queuedMessage->addColumn('sender', 'string');
        $queuedMessage->addColumn('from_address', 'string');
        $queuedMessage->addColumn('from_name', 'string')->setNotnull(false);
        $queuedMessage->addColumn('to_address', 'string');
        $queuedMessage->addColumn('to_name', 'string')->setNotnull(false);
        $queuedMessage->addColumn('subject', 'text');
        $queuedMessage->addColumn('body', 'text');
        $queuedMessage->addColumn('sent_at', 'datetime')->setNotnull(false);
        $queuedMessage->addColumn('failed_at', 'datetime')->setNotnull(false);

        $queuedMessage->setPrimaryKey(['id']);
        $queuedMessage->addForeignKeyConstraint($schema->getTable('email_recipient'), ['source_recipient_id'], ['id']);

        $schema->getTable('email_message')->addColumn('queued_time', 'datetime')->setNotnull(false);

        $recipient = $schema->getTable('email_recipient');
        $recipient->addColumn('queued_time', 'datetime')->setNotnull(false);
        $recipient->addColumn('failed_time', 'datetime')->setNotnull(false);
        $recipient->addColumn('failure_message', 'text')->setNotnull(false);
        $recipient->addColumn('originating_recipient_id', 'integer')->setNotnull(false);
        $recipient->addForeignKeyConstraint($recipient, ['originating_recipient_id'], ['id'], ['onDelete'=>'cascade']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('email_queued_message');
        $schema->getTable('email_message')->dropColumn('queued_time');
        $schema->getTable('email_recipient')
            ->dropColumn('queued_time')
            ->dropColumn('failed_time')
            ->dropColumn('failure_message')
            ->dropColumn('originating_recipient')
            ->removeForeignKey('FK_670F64622C701E7E')
        ;
    }
}
