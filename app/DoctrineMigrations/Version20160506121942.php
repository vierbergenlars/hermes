<?php

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
