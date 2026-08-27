<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260521180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stock_advisor table (Calypso read-only, IF NOT EXISTS)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS stock_advisor (
                Esquema VARCHAR(30) NULL,
                Codigo_Calipso VARCHAR(50) NOT NULL,
                Articulo VARCHAR(100) NULL,
                Codigo_IdeaConnector VARCHAR(100) NULL,
                Codigo_Rockwell VARCHAR(250) NULL,
                Descripcion VARCHAR(500) NULL,
                Descripcion_Ideaconector VARCHAR(500) NULL,
                Descripcion_Tecnica_Ideaconector LONGTEXT NULL,
                Imagen VARCHAR(1000) NULL,
                Soluciones VARCHAR(500) NULL,
                Categoria_Advisor VARCHAR(200) NULL,
                SubCategoria_Advisor VARCHAR(200) NULL,
                Proveedor VARCHAR(200) NULL,
                Marca VARCHAR(100) NULL,
                Descripcion_Advisor TEXT NULL,
                Visible_Advisor VARCHAR(100) NULL,
                Tags TEXT NULL,
                Stock DECIMAL(15,4) NULL DEFAULT 0,
                PRIMARY KEY (Codigo_Calipso),
                INDEX idx_categoria (Categoria_Advisor),
                INDEX idx_subcategoria (SubCategoria_Advisor),
                INDEX idx_proveedor (Proveedor)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS stock_advisor');
    }
}
