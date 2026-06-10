<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610191027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna serviceMotivo a la tabla Servicios';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE Servicios ADD serviceMotivo VARCHAR(50) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE Servicios DROP COLUMN serviceMotivo
        SQL);
    }
}
