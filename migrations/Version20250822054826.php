<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove UNIQUE constraint from national_id_number field in user table
 */
final class Version20250901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove UNIQUE constraint from national_id_number field to allow duplicates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO role (name, type) VALUES ('ROLE_ADMINISTRADOR','external')");
    }

    public function down(Schema $schema): void
    {
    }
}