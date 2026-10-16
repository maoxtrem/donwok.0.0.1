<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261011000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea las cuentas operativas Caja Base y Caja Menor';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO cuenta_financiera (nombre, tipo, saldo_inicial, fecha_creacion, fecha_actualizacion, activa, usar_en_facturacion)\n            SELECT 'Caja Base', 'CAJA', 200000.00, NOW(), NOW(), 1, 0\n            WHERE NOT EXISTS (SELECT 1 FROM cuenta_financiera WHERE nombre = 'Caja Base')");
        $this->addSql("INSERT INTO cuenta_financiera (nombre, tipo, saldo_inicial, fecha_creacion, fecha_actualizacion, activa, usar_en_facturacion)\n            SELECT 'Caja Menor', 'CAJA', 0.00, NOW(), NOW(), 1, 0\n            WHERE NOT EXISTS (SELECT 1 FROM cuenta_financiera WHERE nombre = 'Caja Menor')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM cuenta_financiera WHERE nombre IN ('Caja Base', 'Caja Menor')");
    }
}
