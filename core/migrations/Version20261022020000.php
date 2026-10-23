<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega horario y días activos a los puestos de trabajo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puestos_trabajo ADD hora_inicio TIME DEFAULT NULL, ADD hora_cierre TIME DEFAULT NULL, ADD dias_activos JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puestos_trabajo DROP hora_inicio, DROP hora_cierre, DROP dias_activos');
    }
}
