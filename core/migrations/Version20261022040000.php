<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega denominación mínima para redondeo de pagos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puestos_trabajo ADD denominacion_minima INT DEFAULT 50 NOT NULL');
        $this->addSql('ALTER TABLE turnos_puesto ADD denominacion_minima INT NOT NULL AFTER valor_hora');
        $this->addSql('UPDATE turnos_puesto SET denominacion_minima = 50 WHERE denominacion_minima IS NULL OR denominacion_minima <= 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE turnos_puesto DROP denominacion_minima');
        $this->addSql('ALTER TABLE puestos_trabajo DROP denominacion_minima');
    }
}
