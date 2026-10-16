<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renombra Caja Principal y crea Cuenta Bancaria';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE cuenta_financiera SET nombre = 'Caja Registradora' WHERE nombre = 'Caja Principal'");
        $this->addSql("INSERT INTO cuenta_financiera (nombre, tipo, saldo_inicial, fecha_creacion, fecha_actualizacion, activa, usar_en_facturacion, incluir_en_arqueo, mostrar_en_balance, usar_en_egresos, usar_en_cuentas_por_pagar)
            SELECT 'Cuenta Bancaria', 'BANCO', 0.00, NOW(), NOW(), 1, 0, 1, 1, 1, 1
            WHERE NOT EXISTS (SELECT 1 FROM cuenta_financiera WHERE nombre = 'Cuenta Bancaria')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM cuenta_financiera WHERE nombre = 'Cuenta Bancaria'");
        $this->addSql("UPDATE cuenta_financiera SET nombre = 'Caja Principal' WHERE nombre = 'Caja Registradora'");
    }
}
