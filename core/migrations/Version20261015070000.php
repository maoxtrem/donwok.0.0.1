<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Guarda el valor contado de cada cuenta en la auditoría del cierre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transferencias_arqueo ADD valor_contado NUMERIC(12, 2) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transferencias_arqueo DROP valor_contado');
    }
}
