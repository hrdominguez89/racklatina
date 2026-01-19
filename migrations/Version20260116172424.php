<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega columna href a la tabla carousel
 */
final class Version20260116172424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna href a la tabla carousel para enlaces clickeables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE carousel ADD href VARCHAR(500) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE carousel DROP href
        SQL);
    }
}
