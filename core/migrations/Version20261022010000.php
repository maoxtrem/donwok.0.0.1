<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea la tabla pública de puestos de trabajo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE puestos_trabajo (id INT AUTO_INCREMENT NOT NULL, titulo VARCHAR(150) NOT NULL, descripcion LONGTEXT DEFAULT NULL, ubicacion VARCHAR(120) DEFAULT NULL, tipo_contrato VARCHAR(80) DEFAULT NULL, salario NUMERIC(12, 2) DEFAULT NULL, hora_inicio TIME DEFAULT NULL, hora_cierre TIME DEFAULT NULL, dias_activos JSON NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, fecha_creacion DATETIME NOT NULL, fecha_actualizacion DATETIME NOT NULL, fecha_eliminacion DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE puestos_trabajo');
    }
}
