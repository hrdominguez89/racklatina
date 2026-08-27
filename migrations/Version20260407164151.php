<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407164151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add active_proyecto_id to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD active_proyecto_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP active_proyecto_id');
    }
}
