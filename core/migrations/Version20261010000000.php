<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261010000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permite habilitar o deshabilitar cuentas financieras';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera ADD activa TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera DROP activa');
    }
}
