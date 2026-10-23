<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega valor por hora y turnos de puestos de trabajo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puestos_trabajo ADD valor_hora NUMERIC(12, 2) DEFAULT NULL, ADD denominacion_minima INT DEFAULT 50 NOT NULL');
        $this->addSql('CREATE TABLE turnos_puesto (id INT AUTO_INCREMENT NOT NULL, puesto_id INT NOT NULL, inicio DATETIME NOT NULL, cierre DATETIME DEFAULT NULL, valor_hora NUMERIC(12, 2) NOT NULL, denominacion_minima INT NOT NULL, monto_pagar NUMERIC(12, 2) DEFAULT NULL, fecha_creacion DATETIME NOT NULL, fecha_actualizacion DATETIME NOT NULL, fecha_eliminacion DATETIME DEFAULT NULL, INDEX IDX_TURNO_PUESTO (puesto_id), PRIMARY KEY(id), CONSTRAINT FK_TURNO_PUESTO FOREIGN KEY (puesto_id) REFERENCES puestos_trabajo (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE turnos_puesto');
        $this->addSql('ALTER TABLE puestos_trabajo DROP valor_hora');
    }
}
