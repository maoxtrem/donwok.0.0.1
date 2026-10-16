<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Guarda la distribución de pagos de cada factura por cuenta';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE facturas ADD pagos JSON NULL");
        $this->addSql("UPDATE facturas SET pagos = JSON_ARRAY()");
        $this->addSql("UPDATE facturas f JOIN cuenta_financiera c ON c.nombre = 'Caja Principal' SET f.pagos = JSON_ARRAY(JSON_OBJECT('cuenta_id', c.id, 'monto', f.pago_efectivo, 'tipo', c.tipo)) WHERE f.pago_efectivo > 0");
        $this->addSql("UPDATE facturas f JOIN cuenta_financiera c ON c.nombre = 'Cuenta Nequi' SET f.pagos = JSON_ARRAY_APPEND(f.pagos, '$', JSON_OBJECT('cuenta_id', c.id, 'monto', f.pago_nequi, 'tipo', c.tipo)) WHERE f.pago_nequi > 0");
        $this->addSql('ALTER TABLE facturas MODIFY pagos JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE facturas DROP pagos');
    }
}
