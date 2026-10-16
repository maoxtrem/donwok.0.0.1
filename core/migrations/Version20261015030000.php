<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega cuentas temporales y auditoría de traslados de arqueo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera ADD es_temporal TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE TABLE transferencias_arqueo (id INT AUTO_INCREMENT NOT NULL, cuenta_temporal_id INT NOT NULL, cuenta_origen_id INT NOT NULL, cuenta_destino_id INT DEFAULT NULL, cuenta_origen_nombre VARCHAR(100) NOT NULL, cuenta_temporal_nombre VARCHAR(120) NOT NULL, cuenta_destino_nombre VARCHAR(100) DEFAULT NULL, monto NUMERIC(12, 2) NOT NULL, auditado_por VARCHAR(150) NOT NULL, movido_por VARCHAR(150) DEFAULT NULL, estado VARCHAR(20) NOT NULL, fecha_arqueo DATETIME NOT NULL, fecha_traslado DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE transferencias_arqueo');
        $this->addSql('ALTER TABLE cuenta_financiera DROP es_temporal');
    }
}
