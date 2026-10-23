<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega saldo base a las cuentas financieras';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera ADD saldo_base NUMERIC(12, 2) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera DROP saldo_base');
    }
}
