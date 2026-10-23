<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega código protegido para cerrar turnos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE turnos_puesto ADD codigo_cierre_hash VARCHAR(64) DEFAULT NULL");
        $this->addSql("UPDATE turnos_puesto SET codigo_cierre_hash = SHA2(CONCAT(id, inicio), 256) WHERE codigo_cierre_hash IS NULL");
        $this->addSql("ALTER TABLE turnos_puesto MODIFY codigo_cierre_hash VARCHAR(64) NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE turnos_puesto DROP codigo_cierre_hash');
    }
}
