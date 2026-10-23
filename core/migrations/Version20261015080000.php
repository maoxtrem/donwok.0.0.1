<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fuerza saldo base cero para cuentas temporales';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE cuenta_financiera SET saldo_base = 0 WHERE es_temporal = 1');
    }

    public function down(Schema $schema): void
    {
    }
}
