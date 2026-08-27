<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega roles externos Ingeniero N1 e Ingeniero N2';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO role (name, type) SELECT 'ROLE_INGENIERO_N1', 'external' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM role WHERE name = 'ROLE_INGENIERO_N1')");
        $this->addSql("INSERT INTO role (name, type) SELECT 'ROLE_INGENIERO_N2', 'external' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM role WHERE name = 'ROLE_INGENIERO_N2')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role WHERE name IN ('ROLE_INGENIERO_N1', 'ROLE_INGENIERO_N2')");
    }
}
