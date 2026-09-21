<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260920220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea los datos configurables de la empresa';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE datos_empresa (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(150) NOT NULL, nit VARCHAR(50) NOT NULL, frase_dia VARCHAR(255) DEFAULT NULL, telefono_domicilios VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("INSERT INTO datos_empresa (nombre, nit, frase_dia, telefono_domicilios) VALUES ('', '', NULL, '')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE datos_empresa');
    }
}
