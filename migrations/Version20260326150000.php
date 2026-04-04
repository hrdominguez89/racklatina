<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Catalogo e-commerce: articulos_ecommerce, proyectos, proyecto_items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE articulos_ecommerce (
                id INT AUTO_INCREMENT NOT NULL,
                esquema VARCHAR(30) DEFAULT NULL,
                codigo_calipso VARCHAR(50) NOT NULL,
                articulo_ideaconnector VARCHAR(100) DEFAULT NULL,
                codigo_ideaconnector VARCHAR(100) DEFAULT NULL,
                codigo_rockwell VARCHAR(100) DEFAULT NULL,
                descripcion VARCHAR(500) DEFAULT NULL,
                descripcion_ideaconector VARCHAR(500) DEFAULT NULL,
                descripcion_tecnica LONGTEXT DEFAULT NULL,
                imagen VARCHAR(1000) DEFAULT NULL,
                soluciones VARCHAR(500) DEFAULT NULL,
                categoria_advisor VARCHAR(200) DEFAULT NULL,
                subcategoria_advisor VARCHAR(200) DEFAULT NULL,
                id_bu VARCHAR(20) DEFAULT NULL,
                bu VARCHAR(100) DEFAULT NULL,
                id_proveedor VARCHAR(20) DEFAULT NULL,
                proveedor VARCHAR(200) DEFAULT NULL,
                marca VARCHAR(100) DEFAULT NULL,
                activo TINYINT(1) DEFAULT 1 NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE INDEX UNIQ_ARTICULOS_CODIGO (codigo_calipso),
                INDEX idx_categoria (categoria_advisor),
                INDEX idx_subcategoria (subcategoria_advisor),
                INDEX idx_bu (bu),
                INDEX idx_proveedor (proveedor),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE proyectos (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                descripcion LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                deleted_at DATETIME DEFAULT NULL,
                INDEX IDX_PROYECTOS_USER (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE proyectos ADD CONSTRAINT FK_PROYECTOS_USER FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE proyecto_items (
                id INT AUTO_INCREMENT NOT NULL,
                proyecto_id INT NOT NULL,
                articulo_id INT NOT NULL,
                cantidad INT DEFAULT 1 NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_PI_PROYECTO (proyecto_id),
                INDEX IDX_PI_ARTICULO (articulo_id),
                UNIQUE INDEX uq_proyecto_articulo (proyecto_id, articulo_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE proyecto_items
                ADD CONSTRAINT FK_PI_PROYECTO FOREIGN KEY (proyecto_id) REFERENCES proyectos (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_PI_ARTICULO FOREIGN KEY (articulo_id) REFERENCES articulos_ecommerce (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyecto_items DROP FOREIGN KEY FK_PI_PROYECTO');
        $this->addSql('ALTER TABLE proyecto_items DROP FOREIGN KEY FK_PI_ARTICULO');
        $this->addSql('ALTER TABLE proyectos DROP FOREIGN KEY FK_PROYECTOS_USER');
        $this->addSql('DROP TABLE proyecto_items');
        $this->addSql('DROP TABLE proyectos');
        $this->addSql('DROP TABLE articulos_ecommerce');
    }
}
