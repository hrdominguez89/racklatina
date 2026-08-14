<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260729202834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla encuesta_nps para encuesta NPS anual';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE encuesta_nps (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, score SMALLINT NOT NULL, comentario LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_F1C8C4DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE encuesta_nps ADD CONSTRAINT FK_F1C8C4DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE encuesta_nps DROP FOREIGN KEY FK_F1C8C4DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE encuesta_nps
        SQL);
    }
}
