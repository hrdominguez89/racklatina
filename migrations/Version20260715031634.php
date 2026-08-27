<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715031634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega precio_unitario_usd en proyecto_items y precio_total_usd en proyectos (snapshot al finalizar)';
    }

    public function up(Schema $schema): void
    {
        $colsPi = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND COLUMN_NAME = 'precio_unitario_usd'"
        );
        if (!in_array('precio_unitario_usd', $colsPi)) {
            $this->connection->executeStatement(
                "ALTER TABLE proyecto_items ADD precio_unitario_usd NUMERIC(12, 2) DEFAULT NULL"
            );
        } else {
            $this->write('La columna precio_unitario_usd ya existe en proyecto_items, se omite.');
        }

        $colsP = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyectos'
               AND COLUMN_NAME = 'precio_total_usd'"
        );
        if (!in_array('precio_total_usd', $colsP)) {
            $this->connection->executeStatement(
                "ALTER TABLE proyectos ADD precio_total_usd NUMERIC(14, 2) DEFAULT NULL"
            );
        } else {
            $this->write('La columna precio_total_usd ya existe en proyectos, se omite.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyecto_items DROP precio_unitario_usd');
        $this->addSql('ALTER TABLE proyectos DROP precio_total_usd');
    }
}
