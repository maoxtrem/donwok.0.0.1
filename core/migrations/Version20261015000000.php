<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configura si cada cuenta participa en arqueo y balance general';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera ADD incluir_en_arqueo TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE cuenta_financiera ADD mostrar_en_balance TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cuenta_financiera DROP incluir_en_arqueo');
        $this->addSql('ALTER TABLE cuenta_financiera DROP mostrar_en_balance');
    }
}
