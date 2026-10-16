<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configura cuentas permitidas para egresos y cuentas por pagar';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera ADD usar_en_egresos TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE cuenta_financiera ADD usar_en_cuentas_por_pagar TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera DROP usar_en_egresos');
        $this->addSql('ALTER TABLE cuenta_financiera DROP usar_en_cuentas_por_pagar');
    }
}
