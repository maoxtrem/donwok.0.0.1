<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023020000 extends AbstractMigration
{
    public function getDescription(): string { return 'Agrega URL inicial a los grupos'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE grupos ADD url_inicio VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE grupos DROP url_inicio');
    }
}
