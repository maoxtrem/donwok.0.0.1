<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrupa las cuentas temporales por identificador de cierre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE transferencias_arqueo ADD identificador_cierre VARCHAR(50) DEFAULT 'LEGACY' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transferencias_arqueo DROP identificador_cierre');
    }
}
