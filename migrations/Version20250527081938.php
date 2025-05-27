<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250527081938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE customer_request (id INT AUTO_INCREMENT NOT NULL, user_request_id INT NOT NULL, user_update_id INT DEFAULT NULL, request_type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, data JSON NOT NULL COMMENT '(DC2Type:json)', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_274A2B21E5197E49 (user_request_id), INDEX IDX_274A2B21D5766755 (user_update_id), INDEX IDX_274A2B217B00651C (status), INDEX IDX_274A2B21F37970D3 (request_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE customer_request ADD CONSTRAINT FK_274A2B21E5197E49 FOREIGN KEY (user_request_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE customer_request ADD CONSTRAINT FK_274A2B21D5766755 FOREIGN KEY (user_update_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE customer_request DROP FOREIGN KEY FK_274A2B21E5197E49
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE customer_request DROP FOREIGN KEY FK_274A2B21D5766755
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE customer_request
        SQL);
    }
}
