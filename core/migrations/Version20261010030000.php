<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Guarda cliente y descripción de domicilio en los pedidos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facturas ADD cliente_nombre VARCHAR(100) DEFAULT NULL, ADD domicilio_descripcion LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facturas DROP cliente_nombre, DROP domicilio_descripcion');
    }
}
