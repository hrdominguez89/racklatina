<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715031634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega precio_unitario_usd en proyecto_items y precio_total_usd en proyectos (snapshot al finalizar)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE proyecto_items ADD precio_unitario_usd NUMERIC(12, 2) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE proyectos ADD precio_total_usd NUMERIC(14, 2) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE proyecto_items DROP precio_unitario_usd
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE proyectos DROP precio_total_usd
        SQL);
    }
}
