<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Relaciona los detalles de pedido con el producto del catálogo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE factura_detalles ADD producto_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE factura_detalles DROP producto_id');
    }
}
