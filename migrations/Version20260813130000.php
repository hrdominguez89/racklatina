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
        $this->addSql("INSERT INTO role (name, type) VALUES ('ROLE_INGENIERO_N1', 'external')");
        $this->addSql("INSERT INTO role (name, type) VALUES ('ROLE_INGENIERO_N2', 'external')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM role WHERE name IN ('ROLE_INGENIERO_N1', 'ROLE_INGENIERO_N2')");
    }
}
