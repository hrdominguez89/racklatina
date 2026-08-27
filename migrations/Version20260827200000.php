<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop FK_PI_ARTICULO from proyecto_items and drop articulos_ecommerce table';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // Eliminar FK si existe
        $fks = $this->connection->fetchFirstColumn(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyecto_items'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'
               AND CONSTRAINT_NAME = 'FK_PI_ARTICULO'"
        );
        if (!empty($fks)) {
            $this->connection->executeStatement('ALTER TABLE proyecto_items DROP FOREIGN KEY FK_PI_ARTICULO');
        }

        // Eliminar tabla si existe
        $this->connection->executeStatement('DROP TABLE IF EXISTS articulos_ecommerce');
    }

    public function down(Schema $schema): void
    {
        $this->write('No se puede revertir automáticamente.');
    }
}
