<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609045346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data ADD sector_id INT DEFAULT NULL, ADD sector_extra_data VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data ADD CONSTRAINT FK_F32910BEDE95C867 FOREIGN KEY (sector_id) REFERENCES sectors (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F32910BEDE95C867 ON external_user_data (sector_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data DROP FOREIGN KEY FK_F32910BEDE95C867
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F32910BEDE95C867 ON external_user_data
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data DROP sector_id, DROP sector_extra_data
        SQL);
    }
}
