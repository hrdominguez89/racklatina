<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columnas articulo_codigo y comment a proyecto_items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE proyecto_items
                ADD articulo_codigo VARCHAR(50) DEFAULT NULL,
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE proyecto_items
                DROP COLUMN articulo_codigo,
        SQL);
    }
}
