<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128131017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ServiciosAdjuntos table and remove factura_filepath from Servicios';
    }

    public function up(Schema $schema): void
    {
        // Create ServiciosAdjuntos table
        $this->addSql('CREATE TABLE ServiciosAdjuntos (id INT AUTO_INCREMENT NOT NULL, servicio_id INT NOT NULL, filename VARCHAR(255) NOT NULL, filepath VARCHAR(255) NOT NULL, INDEX IDX_9A070DA571CAA3E7 (servicio_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ServiciosAdjuntos ADD CONSTRAINT FK_9A070DA571CAA3E7 FOREIGN KEY (servicio_id) REFERENCES Servicios (serviceID)');

    }

    public function down(Schema $schema): void
    {
        // Drop ServiciosAdjuntos table
        $this->addSql('ALTER TABLE ServiciosAdjuntos DROP FOREIGN KEY FK_9A070DA571CAA3E7');
        $this->addSql('DROP TABLE ServiciosAdjuntos');
    }
}
