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
