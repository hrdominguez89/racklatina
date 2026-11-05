<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105172604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ampliar serviceFalla en Servicios de VARCHAR(500) a TEXT (descripciones largas causaban error 500 al guardar)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE Servicios MODIFY serviceFalla TEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE Servicios MODIFY serviceFalla VARCHAR(500) DEFAULT NULL
        SQL);
    }
}
