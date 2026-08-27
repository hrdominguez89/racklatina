<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510164534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna status a proyectos';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyectos'
               AND COLUMN_NAME = 'status'"
        );

        if (!in_array('status', $columns)) {
            $this->addSql("ALTER TABLE proyectos ADD status VARCHAR(20) NOT NULL DEFAULT 'in_progress'");
        } else {
            $this->warnIf(true, 'La columna status ya existe en proyectos, se omite.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyectos DROP status');
    }
}
