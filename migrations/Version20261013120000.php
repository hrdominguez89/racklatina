<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add nota_solicitud TEXT column to proyectos (aclaraciones al solicitar cotización)';
    }

    public function up(Schema $schema): void
    {
        $columns = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'proyectos'
               AND COLUMN_NAME = 'nota_solicitud'"
        );

        if (!in_array('nota_solicitud', $columns)) {
            $this->addSql("ALTER TABLE proyectos ADD nota_solicitud LONGTEXT DEFAULT NULL");
        } else {
            $this->warnIf(true, 'La columna nota_solicitud ya existe en proyectos, se omite.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE proyectos DROP COLUMN nota_solicitud");
    }
}
