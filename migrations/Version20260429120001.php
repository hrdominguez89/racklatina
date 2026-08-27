<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna articulo_codigo a proyecto_items';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND COLUMN_NAME = 'articulo_codigo'"
        );

        if (!in_array('articulo_codigo', $columns)) {
            $this->addSql("ALTER TABLE proyecto_items ADD articulo_codigo VARCHAR(50) DEFAULT NULL");
        } else {
            $this->warnIf(true, 'La columna articulo_codigo ya existe en proyecto_items, se omite.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyecto_items DROP COLUMN articulo_codigo');
    }
}
