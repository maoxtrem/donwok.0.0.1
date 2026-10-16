<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configura las cuentas disponibles para facturación';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera ADD usar_en_facturacion TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE cuenta_financiera SET usar_en_facturacion = 1 WHERE nombre IN ('Caja Principal', 'Cuenta Nequi')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera DROP usar_en_facturacion');
    }
}
