<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega frecuencia de aplicación a las deducciones de caja';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deducciones_caja ADD cada_cierre TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE prestamos ADD deduccion_caja_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE prestamos ADD CONSTRAINT FK_PRESTAMO_DEDUCCION FOREIGN KEY (deduccion_caja_id) REFERENCES deducciones_caja (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_PRESTAMO_DEDUCCION ON prestamos (deduccion_caja_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestamos DROP FOREIGN KEY FK_PRESTAMO_DEDUCCION');
        $this->addSql('DROP INDEX IDX_PRESTAMO_DEDUCCION ON prestamos');
        $this->addSql('ALTER TABLE prestamos DROP deduccion_caja_id');
        $this->addSql('ALTER TABLE deducciones_caja DROP cada_cierre');
    }
}
