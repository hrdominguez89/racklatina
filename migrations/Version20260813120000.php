<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega campos de no-cliente a external_user_data: domicilio, codigo_postal, localidad, tipo_cliente';
    }

    public function up(Schema $schema): void
    {
        $existing = $this->connection->fetchFirstColumn(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'external_user_data'
               AND COLUMN_NAME IN ('domicilio', 'codigo_postal', 'localidad', 'tipo_cliente')"
        );

        $toAdd = [];
        if (!in_array('domicilio', $existing))      $toAdd[] = "ADD domicilio VARCHAR(255) DEFAULT NULL";
        if (!in_array('codigo_postal', $existing))  $toAdd[] = "ADD codigo_postal VARCHAR(10) DEFAULT NULL";
        if (!in_array('localidad', $existing))      $toAdd[] = "ADD localidad VARCHAR(100) DEFAULT NULL";
        if (!in_array('tipo_cliente', $existing))   $toAdd[] = "ADD tipo_cliente VARCHAR(100) DEFAULT NULL";

        if (!empty($toAdd)) {
            $this->connection->executeStatement(
                "ALTER TABLE external_user_data " . implode(', ', $toAdd)
            );
        } else {
            $this->warnIf(true, 'Todas las columnas ya existen en external_user_data, se omite.');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE external_user_data
            DROP domicilio,
            DROP codigo_postal,
            DROP localidad,
            DROP tipo_cliente
        ");
    }
}
