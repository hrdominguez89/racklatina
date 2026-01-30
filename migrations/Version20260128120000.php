<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crea la tabla encuesta_respuesta para almacenar las respuestas de la encuesta de satisfacción
 */
final class Version20260128120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla encuesta_respuesta para la encuesta de satisfacción de clientes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE encuesta_respuesta (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                calificacion SMALLINT NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_ENCUESTA_USER (user_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_ENCUESTA_USER FOREIGN KEY (user_id) REFERENCES user (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE encuesta_respuesta
        SQL);
    }
}
