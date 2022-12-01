<?php

declare(strict_types=1);

namespace MigrationsDefault;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221201163243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE batch (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, statut BOOLEAN NOT NULL, nom VARCHAR(32) NOT NULL, description VARCHAR(128) NOT NULL, date_modification DATETIME DEFAULT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('DROP TABLE repartition');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE repartition (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL COLLATE "BINARY", name VARCHAR(128) NOT NULL COLLATE "BINARY", component CLOB NOT NULL COLLATE "BINARY", type VARCHAR(16) NOT NULL COLLATE "BINARY", severity VARCHAR(8) NOT NULL COLLATE "BINARY", setup INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('DROP TABLE batch');
    }
}
