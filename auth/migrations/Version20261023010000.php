<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023010000 extends AbstractMigration
{
    public function getDescription(): string { return 'Crea grupos de usuarios y permisos por URL'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE grupos (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, urls JSON NOT NULL, PRIMARY KEY(id), UNIQUE INDEX UNIQ_GRUPO_NOMBRE (nombre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE usuarios_grupos (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_USUARIO_GRUPO_USER (user_id), INDEX IDX_USUARIO_GRUPO_GROUP (group_id), PRIMARY KEY(user_id, group_id), CONSTRAINT FK_USUARIO_GRUPO_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE, CONSTRAINT FK_USUARIO_GRUPO_GROUP FOREIGN KEY (group_id) REFERENCES grupos (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE usuarios_grupos'); $this->addSql('DROP TABLE grupos'); }
}
