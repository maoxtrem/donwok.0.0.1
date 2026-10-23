<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Conserva el historial de ediciones de los detalles de pedidos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE facturas ADD editada TINYINT(1) DEFAULT 0 NOT NULL, ADD fecha_ultima_edicion DATETIME DEFAULT NULL, ADD editada_por VARCHAR(150) DEFAULT NULL");
        $this->addSql("ALTER TABLE factura_detalles ADD estado VARCHAR(20) DEFAULT 'ACTIVO' NOT NULL, ADD fecha_eliminacion DATETIME DEFAULT NULL, ADD eliminado_por VARCHAR(150) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facturas DROP editada, DROP fecha_ultima_edicion, DROP editada_por');
        $this->addSql('ALTER TABLE factura_detalles DROP estado, DROP fecha_eliminacion, DROP eliminado_por');
    }
}
