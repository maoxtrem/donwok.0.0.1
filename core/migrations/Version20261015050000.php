<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega indicador para mostrar e imprimir deducciones en el cierre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deducciones_caja ADD mostrar_en_cierre TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deducciones_caja DROP mostrar_en_cierre');
    }
}
