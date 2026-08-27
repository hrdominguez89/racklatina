<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega rol externo ROLE_USER para usuarios no-clientes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO role (name, type) SELECT 'ROLE_USER', 'external' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM role WHERE name = 'ROLE_USER')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role WHERE name = 'ROLE_USER'");
    }
}
