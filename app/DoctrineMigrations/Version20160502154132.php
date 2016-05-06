<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160502154132 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $emailTransport = $schema->createTable('email_transport');
        $emailTransport->addColumn('id', 'integer')->setAutoincrement(true);
        $emailTransport->addColumn('name', 'string');
        $emailTransport->addColumn('delivery_starttime', 'datetime');
        $emailTransport->addColumn('delivery_period_duration', 'string');
        $emailTransport->addColumn('delivery_period_maxmails','integer');
        $emailTransport->addColumn('delivery_latency', 'string');
        $emailTransport->addColumn('transport_type', 'string');
        $emailTransport->addColumn('mail_host', 'string')->setNotnull(false);
        $emailTransport->addColumn('mail_port', 'integer')->setNotnull(false);
        $emailTransport->addColumn('mail_security', 'string')->setNotnull(false);
        $emailTransport->addColumn('mail_username', 'string')->setNotnull(false);
        $emailTransport->addColumn('mail_password', 'string')->setNotnull(false);

        $emailTransport->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('email_transport');
    }
}
