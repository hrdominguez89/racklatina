<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add leadtime_resultado JSON column to proyecto_items';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND COLUMN_NAME = 'leadtime_resultado'"
        );

        if (!in_array('leadtime_resultado', $columns)) {
            $this->addSql("ALTER TABLE proyecto_items ADD leadtime_resultado JSON NULL DEFAULT NULL COMMENT '(DC2Type:json)'");
        } else {
            $this->warnIf(true, 'La columna leadtime_resultado ya existe en proyecto_items, se omite.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE proyecto_items DROP COLUMN leadtime_resultado");
    }
}
