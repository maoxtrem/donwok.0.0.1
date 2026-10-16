<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega deducciones configurables para el cierre de caja';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE deducciones_caja (id INT AUTO_INCREMENT NOT NULL, empresa_id INT NOT NULL, nombre VARCHAR(150) NOT NULL, tipo VARCHAR(12) NOT NULL, valor NUMERIC(12, 2) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_DEDUCCION_EMPRESA (empresa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE deducciones_caja ADD CONSTRAINT FK_DEDUCCION_EMPRESA FOREIGN KEY (empresa_id) REFERENCES datos_empresa (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE deducciones_caja');
    }
}
