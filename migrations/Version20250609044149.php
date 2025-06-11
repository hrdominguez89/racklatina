<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609044149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE sectors (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, requires_data TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
        INSERT INTO sectors (name, requires_data) VALUES
        ('Comercial', 0),
        ('RRHH', 0),
        ('Administración y finanzas', 0),
        ('Compras', 0),
        ('Logística', 0),
        ('Operaciones', 0),
        ('Marketing', 0),
        ('Sistemas', 0),
        ('Proyectos', 0),
        ('Ingeniería', 0),
        ('Mantenimiento', 0),
        ('Seguridad e higiene/medioambiente/sustentabilidad', 0),
        ('Otro (completar)', 1);
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE sectors
        SQL);
    }
}
