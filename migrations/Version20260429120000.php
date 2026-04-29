<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega columna rubro a proyectos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE proyectos ADD rubro VARCHAR(100) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE proyectos DROP COLUMN rubro
        SQL);
    }
}
