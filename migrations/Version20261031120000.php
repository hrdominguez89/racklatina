<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261031120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Política de contraseñas: password_changed_at en user + tabla password_history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD password_changed_at DATETIME DEFAULT NULL');
        // Backfill: usuarios existentes arrancan el período de 90 días desde el deploy de este
        // cambio, no se les fuerza el cambio de contraseña de inmediato.
        $this->addSql('UPDATE user SET password_changed_at = NOW() WHERE password_changed_at IS NULL');

        $this->addSql('CREATE TABLE password_history (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_password_history_user (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE password_history ADD CONSTRAINT FK_password_history_user
            FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_history DROP FOREIGN KEY FK_password_history_user');
        $this->addSql('DROP TABLE password_history');
        $this->addSql('ALTER TABLE user DROP password_changed_at');
    }
}
