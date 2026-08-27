<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Elimina columna articulo_id de proyecto_items si existe';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND COLUMN_NAME = 'articulo_id'"
        );

        if (in_array('articulo_id', $columns)) {
            // Quitar solo FOREIGN KEYs que referencien articulo_id
            $fks = $this->connection->fetchFirstColumn(
                "SELECT kcu.CONSTRAINT_NAME
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                 JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                   ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                  AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
                  AND tc.TABLE_NAME = kcu.TABLE_NAME
                 WHERE kcu.TABLE_SCHEMA = DATABASE()
                   AND kcu.TABLE_NAME = 'proyecto_items'
                   AND kcu.COLUMN_NAME = 'articulo_id'
                   AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'"
            );
            foreach ($fks as $fk) {
                $this->addSql("ALTER TABLE proyecto_items DROP FOREIGN KEY `{$fk}`");
            }

            $this->addSql('ALTER TABLE proyecto_items DROP COLUMN articulo_id');
        } else {
            $this->warnIf(true, 'La columna articulo_id no existe en proyecto_items, se omite.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyecto_items ADD articulo_id INT DEFAULT NULL');
    }
}
