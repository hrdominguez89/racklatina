<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127152015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add factura_filepath column to servicios table';
    }

    public function up(Schema $schema): void
    {
        // Add factura_filepath column to servicios table
        $this->addSql('ALTER TABLE servicios ADD factura_filepath VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove factura_filepath column from servicios table
        $this->addSql('ALTER TABLE servicios DROP factura_filepath');
    }
}
