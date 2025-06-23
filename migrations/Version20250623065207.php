<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623065207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            INSERT INTO role (id, name, type) values (3,'ROLE_SUPER_ADMIN','internal');
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO user_role (user_id, role_id,created_at,updated_at) values (1,3,now(), now());
        SQL);
    }

    public function down(Schema $schema): void
{
    $this->addSql(<<<'SQL'
        DELETE FROM user_role WHERE user_id = 1 AND role_id = 3;
    SQL);

    $this->addSql(<<<'SQL'
        DELETE FROM role WHERE id = 3;
    SQL);
}
}
