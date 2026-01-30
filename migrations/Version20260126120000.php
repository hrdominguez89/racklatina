<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega campos start_at y end_at a la tabla carousel para programar visibilidad
 */
final class Version20260126120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campos start_at y end_at a la tabla carousel para programar visibilidad';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE carousel
            ADD start_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD end_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE carousel DROP start_at, DROP end_at
        SQL);
    }
}
