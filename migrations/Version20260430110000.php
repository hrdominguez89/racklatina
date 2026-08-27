<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix uq_proyecto_articulo: asegurar que sea compuesto (proyecto_id, articulo_codigo) y no solo proyecto_id';
    }

    public function up(Schema $schema): void
    {
        // 1. Verificar si la columna articulo_codigo existe
        $colRows = $this->connection->fetchAllAssociative(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND COLUMN_NAME = 'articulo_codigo'"
        );

        if (empty($colRows)) {
            // La migración 160000 falló a mitad: articulo_codigo nunca se agregó
            $this->write('Columna articulo_codigo no existe, agregándola...');
            $this->connection->executeStatement(
                "ALTER TABLE proyecto_items ADD articulo_codigo VARCHAR(50) NOT NULL DEFAULT ''"
            );
            $this->connection->executeStatement(
                "ALTER TABLE proyecto_items ADD CONSTRAINT FK_PI_ARTICULO FOREIGN KEY (articulo_codigo) REFERENCES articulos_ecommerce (Codigo_Calipso)"
            );
            $this->connection->executeStatement(
                "CREATE INDEX IDX_PI_ARTICULO ON proyecto_items (articulo_codigo)"
            );
        }

        // 2. Verificar las columnas actuales del índice uq_proyecto_articulo
        $indexRows = $this->connection->fetchAllAssociative(
            "SELECT COLUMN_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND INDEX_NAME = 'uq_proyecto_articulo'
             ORDER BY SEQ_IN_INDEX"
        );

        $currentColumns = array_column($indexRows, 'COLUMN_NAME');

        if ($currentColumns !== ['proyecto_id', 'articulo_codigo']) {
            if (!empty($currentColumns)) {
                $this->write(sprintf(
                    'Índice incorrecto detectado: [%s]. Corrigiendo...',
                    implode(', ', $currentColumns)
                ));
                $this->connection->executeStatement(
                    'ALTER TABLE proyecto_items DROP INDEX uq_proyecto_articulo'
                );
            }

            $this->connection->executeStatement(
                'ALTER TABLE proyecto_items ADD UNIQUE INDEX uq_proyecto_articulo (proyecto_id, articulo_codigo)'
            );

            $this->write('Índice uq_proyecto_articulo corregido a (proyecto_id, articulo_codigo).');
        } else {
            $this->write('Índice uq_proyecto_articulo ya es correcto, no se hicieron cambios.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('No se puede revertir automáticamente. Revisar estado manual si es necesario.');
    }
}
