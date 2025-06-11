<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611143551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data ADD segmento VARCHAR(60) DEFAULT NULL, ADD pais VARCHAR(50) DEFAULT NULL, ADD provincia VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_customer ADD empresa VARCHAR(50) DEFAULT NULL, ADD telefono VARCHAR(20) DEFAULT NULL, ADD segmento VARCHAR(50) DEFAULT NULL, ADD sector VARCHAR(50) DEFAULT NULL, ADD cargo VARCHAR(50) DEFAULT NULL, ADD pais VARCHAR(50) DEFAULT NULL, ADD provincia VARCHAR(50) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE external_user_data DROP segmento, DROP pais, DROP provincia
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_customer DROP empresa, DROP telefono, DROP segmento, DROP sector, DROP cargo, DROP pais, DROP provincia
        SQL);
    }
}
