<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add comment column to proyecto_items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyecto_items ADD comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proyecto_items DROP comment');
    }
}
