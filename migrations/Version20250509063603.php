<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250509063603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE external_user_data (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, phone_number VARCHAR(25) NOT NULL, job_title VARCHAR(150) NOT NULL, company_name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_F32910BEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data ADD CONSTRAINT FK_F32910BEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_IDENTIFIER_USERNAME ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD email VARCHAR(255) NOT NULL, ADD first_name VARCHAR(60) NOT NULL, ADD last_name VARCHAR(60) NOT NULL, ADD national_id_number INT NOT NULL, ADD account_token CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)', ADD account_token_expires_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', DROP username
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_NATIONAL_ID_NUMBER ON user (national_id_number)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data DROP FOREIGN KEY FK_F32910BEA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE external_user_data
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_IDENTIFIER_NATIONAL_ID_NUMBER ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD username VARCHAR(180) NOT NULL, DROP email, DROP first_name, DROP last_name, DROP national_id_number, DROP account_token, DROP account_token_expires_at
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username)
        SQL);
    }
}
