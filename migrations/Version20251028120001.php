<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251028120001 extends AbstractMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE role SET name = 'ROLE_ADMINISTRACION' WHERE name = 'ROLE_ADMISTRACION'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE role SET name = 'ROLE_ADMISTRACION' WHERE name = 'ROLE_ADMINISTRACION'");
    }
}