<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121183255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE service_requests (id INT AUTO_INCREMENT NOT NULL, pais_id SMALLINT NOT NULL, provincia_id SMALLINT NOT NULL, marca_id INT NOT NULL, user_id INT NOT NULL, localidad VARCHAR(255) NOT NULL, empresa VARCHAR(255) NOT NULL, contacto VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, direccion VARCHAR(500) NOT NULL, transporte_nombre VARCHAR(255) NOT NULL, cod_catalogo VARCHAR(255) NOT NULL, nro_serie VARCHAR(255) NOT NULL, falla LONGTEXT NOT NULL, adquirido_ultimos12_meses TINYINT(1) NOT NULL, factura_compra_filename VARCHAR(255) DEFAULT NULL, estado VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_82F38D6CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_requests ADD CONSTRAINT FK_82F38D6CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE service_requests DROP FOREIGN KEY FK_82F38D6CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service_requests
        SQL);
    }
}
