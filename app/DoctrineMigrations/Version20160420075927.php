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
class Version20160420075927 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('acl_classes');
        $table->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => 'auto'));
        $table->addColumn('class_type', 'string', array('length' => 200));
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('class_type'));


        $table = $schema->createTable('acl_object_identities');
        $table->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => 'auto'));
        $table->addColumn('class_id', 'integer', array('unsigned' => true));
        $table->addColumn('object_identifier', 'string', array('length' => 100));
        $table->addColumn('parent_object_identity_id', 'integer', array('unsigned' => true, 'notnull' => false));
        $table->addColumn('entries_inheriting', 'boolean');
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('object_identifier', 'class_id'));
        $table->addIndex(array('parent_object_identity_id'));
        $table->addForeignKeyConstraint($table, array('parent_object_identity_id'), array('id'));


        $table = $schema->createTable('acl_object_identity_ancestors');
        $table->addColumn('object_identity_id', 'integer', array('unsigned' => true));
        $table->addColumn('ancestor_id', 'integer', array('unsigned' => true));
        $table->setPrimaryKey(array('object_identity_id', 'ancestor_id'));
        $oidTable = $schema->getTable('acl_object_identities');
        $table->addForeignKeyConstraint($oidTable, array('object_identity_id'), array('id'), array('onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'));
        $table->addForeignKeyConstraint($oidTable, array('ancestor_id'), array('id'), array('onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'));


        $table = $schema->createTable('acl_security_identities');
        $table->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => 'auto'));
        $table->addColumn('identifier', 'string', array('length' => 200));
        $table->addColumn('username', 'boolean');
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('identifier', 'username'));


        $table = $schema->createTable('acl_entries');
        $table->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => 'auto'));
        $table->addColumn('class_id', 'integer', array('unsigned' => true));
        $table->addColumn('object_identity_id', 'integer', array('unsigned' => true, 'notnull' => false));
        $table->addColumn('field_name', 'string', array('length' => 50, 'notnull' => false));
        $table->addColumn('ace_order', 'smallint', array('unsigned' => true));
        $table->addColumn('security_identity_id', 'integer', array('unsigned' => true));
        $table->addColumn('mask', 'integer');
        $table->addColumn('granting', 'boolean');
        $table->addColumn('granting_strategy', 'string', array('length' => 30));
        $table->addColumn('audit_success', 'boolean');
        $table->addColumn('audit_failure', 'boolean');
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('class_id', 'object_identity_id', 'field_name', 'ace_order'));
        $table->addIndex(array('class_id', 'object_identity_id', 'security_identity_id'));
        $table->addForeignKeyConstraint($schema->getTable('acl_classes'), array('class_id'), array('id'), array('onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'));
        $table->addForeignKeyConstraint($schema->getTable('acl_object_identities'), array('object_identity_id'), array('id'), array('onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'));
        $table->addForeignKeyConstraint($schema->getTable('acl_security_identities'), array('security_identity_id'), array('id'), array('onDelete' => 'CASCADE', 'onUpdate' => 'CASCADE'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('acl_classes')
            ->dropTable('acl_entries')
            ->dropTable('acl_object_identities')
            ->dropTable('acl_object_identity_ancestors')
            ->dropTable('acl_security_identities')
        ;

    }
}
