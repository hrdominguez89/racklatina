<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720160812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reemplazo_precio and reemplazo_plazo to proyecto_items';
    }

    public function up(Schema $schema): void
    {
        $existing = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND COLUMN_NAME IN ('reemplazo_precio', 'reemplazo_plazo')"
        );

        if (!in_array('reemplazo_precio', $existing)) {
            $this->connection->executeStatement(
                "ALTER TABLE proyecto_items ADD reemplazo_precio TINYINT(1) NOT NULL DEFAULT 0"
            );
        }
        if (!in_array('reemplazo_plazo', $existing)) {
            $this->connection->executeStatement(
                "ALTER TABLE proyecto_items ADD reemplazo_plazo TINYINT(1) NOT NULL DEFAULT 0"
            );
        }
        if (!empty(array_intersect(['reemplazo_precio', 'reemplazo_plazo'], $existing))) {
            $this->write('Algunas columnas ya existían en proyecto_items, se omitieron.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE proyecto_items DROP COLUMN reemplazo_precio, DROP COLUMN reemplazo_plazo");
    }
}
