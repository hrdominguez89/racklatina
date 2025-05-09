<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509063716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Generar password bcrypt
        $plainPassword = $_ENV['PASSWORD_ADMIN'] ?? null;

        if (!$plainPassword) {
            throw new \RuntimeException('La variable de entorno PASSWORD_ADMIN no está definida.');
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        // Insertar usuario
        $this->addSql("
            INSERT INTO user (password, created_at, updated_at, email, first_name, last_name, national_id_number)
            VALUES (
                '$hashedPassword',
                NOW(),
                NOW(),
                'admin@racklatina.com',
                'Admin',
                'Principal',
                99999999
            )
        ");

        $this->addSql("
            INSERT INTO user_role (user_id, role_id, created_at, updated_at)
            SELECT u.id, r.id, NOW(), NOW()
            FROM user u
            JOIN role r ON r.name = 'ROLE_ADMIN'
            WHERE u.national_id_number = 99999999
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
