<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250527171123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_customer ADD customer_request_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_customer ADD CONSTRAINT FK_61B46A09BFB7BC27 FOREIGN KEY (customer_request_id) REFERENCES customer_request (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_61B46A09BFB7BC27 ON user_customer (customer_request_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_customer DROP FOREIGN KEY FK_61B46A09BFB7BC27
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_61B46A09BFB7BC27 ON user_customer
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_customer DROP customer_request_id
        SQL);
    }
}
