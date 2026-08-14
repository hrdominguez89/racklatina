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
        $this->addSql("ALTER TABLE external_user_data
            ADD domicilio VARCHAR(255) DEFAULT NULL,
            ADD codigo_postal VARCHAR(10) DEFAULT NULL,
            ADD localidad VARCHAR(100) DEFAULT NULL,
            ADD tipo_cliente VARCHAR(100) DEFAULT NULL
        ");
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
