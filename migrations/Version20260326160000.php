<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recrear articulos_ecommerce sin id/timestamps (estructura real externa) + fix FK en proyecto_items';
    }

    public function up(Schema $schema): void
    {
        // 1. Quitar FK en proyecto_items que apuntaba al id INT
        $this->addSql('ALTER TABLE proyecto_items DROP FOREIGN KEY FK_PI_ARTICULO');
        $this->addSql('ALTER TABLE proyecto_items DROP INDEX IDX_PI_ARTICULO');
        $this->addSql('ALTER TABLE proyecto_items DROP COLUMN articulo_id');

        // 2. Recrear articulos_ecommerce con estructura real (PK = Codigo_Calipso, sin id/timestamps/activo)
        $this->addSql('DROP TABLE articulos_ecommerce');
        $this->addSql(<<<'SQL'
            CREATE TABLE articulos_ecommerce (
                Esquema VARCHAR(30) DEFAULT NULL,
                Codigo_Calipso VARCHAR(50) NOT NULL,
                Articulo_IdeaConnector VARCHAR(100) DEFAULT NULL,
                Codigo_IdeaConnector VARCHAR(100) DEFAULT NULL,
                Codigo_Rockwell VARCHAR(100) DEFAULT NULL,
                Descripcion VARCHAR(500) DEFAULT NULL,
                Descripcion_Ideaconector VARCHAR(500) DEFAULT NULL,
                Descripcion_Tecnica_Ideaconector LONGTEXT DEFAULT NULL,
                Imagen VARCHAR(1000) DEFAULT NULL,
                Soluciones VARCHAR(500) DEFAULT NULL,
                Categoria_Advisor VARCHAR(200) DEFAULT NULL,
                SubCategoria_Advisor VARCHAR(200) DEFAULT NULL,
                ID_BU VARCHAR(20) DEFAULT NULL,
                BU VARCHAR(100) DEFAULT NULL,
                Id_Proveedor VARCHAR(20) DEFAULT NULL,
                Proveedor VARCHAR(200) DEFAULT NULL,
                Marca VARCHAR(100) DEFAULT NULL,
                INDEX idx_categoria (Categoria_Advisor),
                INDEX idx_subcategoria (SubCategoria_Advisor),
                INDEX idx_bu (BU),
                INDEX idx_proveedor (Proveedor),
                PRIMARY KEY(Codigo_Calipso)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // 3. Agregar articulo_codigo VARCHAR como FK en proyecto_items
        $this->addSql('ALTER TABLE proyecto_items ADD articulo_codigo VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE proyecto_items ADD CONSTRAINT FK_PI_ARTICULO FOREIGN KEY (articulo_codigo) REFERENCES articulos_ecommerce (Codigo_Calipso)');
        $this->addSql('CREATE INDEX IDX_PI_ARTICULO ON proyecto_items (articulo_codigo)');
        $this->addSql('ALTER TABLE proyecto_items DROP INDEX uq_proyecto_articulo');
        $this->addSql('ALTER TABLE proyecto_items ADD UNIQUE INDEX uq_proyecto_articulo (proyecto_id, articulo_codigo)');
    }

    public function down(Schema $schema): void
    {
        // Revert sería complejo, solo por completitud
        $this->addSql('ALTER TABLE proyecto_items DROP FOREIGN KEY FK_PI_ARTICULO');
        $this->addSql('ALTER TABLE proyecto_items DROP INDEX IDX_PI_ARTICULO');
        $this->addSql('ALTER TABLE proyecto_items DROP INDEX uq_proyecto_articulo');
        $this->addSql('ALTER TABLE proyecto_items DROP COLUMN articulo_codigo');
        $this->addSql('DROP TABLE articulos_ecommerce');
    }
}
