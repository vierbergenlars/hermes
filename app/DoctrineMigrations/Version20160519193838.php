<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160519193838 extends AbstractMigration
{
    private $_to_revert;

    public function __construct(Version $version)
    {
        parent::__construct($version);
        $this->_to_revert = new Version20160502154132($version);
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->_to_revert->down($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->_to_revert->up($schema);
    }
}
