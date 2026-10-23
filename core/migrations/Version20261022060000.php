<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022060000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega código y clave para iniciar puestos de trabajo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE puestos_trabajo ADD codigo_puesto VARCHAR(30) DEFAULT NULL, ADD clave_puesto_hash VARCHAR(64) DEFAULT NULL");
        $this->addSql("UPDATE puestos_trabajo SET codigo_puesto = CONCAT('PUESTO-', id), clave_puesto_hash = SHA2(CONCAT('CLAVE-', id), 256) WHERE codigo_puesto IS NULL");
        $this->addSql("ALTER TABLE puestos_trabajo MODIFY codigo_puesto VARCHAR(30) NOT NULL, MODIFY clave_puesto_hash VARCHAR(64) NOT NULL");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PUESTO_CODIGO ON puestos_trabajo (codigo_puesto)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_PUESTO_CODIGO ON puestos_trabajo');
        $this->addSql('ALTER TABLE puestos_trabajo DROP codigo_puesto, DROP clave_puesto_hash');
    }
}
