<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407161745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add active_cliente to user and cliente_codigo to proyectos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyectos ADD cliente_codigo VARCHAR(22) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD active_cliente VARCHAR(22) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyectos DROP cliente_codigo');
        $this->addSql('ALTER TABLE user DROP active_cliente');
    }
}
