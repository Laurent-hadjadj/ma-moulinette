<?php

declare(strict_types=1);

namespace MigrationsDefault;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221204083652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE repartition');
        $this->addSql('CREATE TEMPORARY TABLE __temp__batch_traitement AS SELECT id, demarrage, resultat, titre, portefeuille, nombre_projet, responsable, temp_execution, date_enregistrement FROM batch_traitement');
        $this->addSql('DROP TABLE batch_traitement');
        $this->addSql('CREATE TABLE batch_traitement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, demarrage VARCHAR(16) NOT NULL, resultat BOOLEAN NOT NULL, titre VARCHAR(32) NOT NULL, portefeuille VARCHAR(32) NOT NULL, nombre_projet INTEGER NOT NULL, responsable VARCHAR(128) NOT NULL, temps_execution INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('INSERT INTO batch_traitement (id, demarrage, resultat, titre, portefeuille, nombre_projet, responsable, temps_execution, date_enregistrement) SELECT id, demarrage, resultat, titre, portefeuille, nombre_projet, responsable, temp_execution, date_enregistrement FROM __temp__batch_traitement');
        $this->addSql('DROP TABLE __temp__batch_traitement');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE repartition (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL COLLATE "BINARY", name VARCHAR(128) NOT NULL COLLATE "BINARY", component CLOB NOT NULL COLLATE "BINARY", type VARCHAR(16) NOT NULL COLLATE "BINARY", severity VARCHAR(8) NOT NULL COLLATE "BINARY", setup INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__batch_traitement AS SELECT id, demarrage, resultat, titre, portefeuille, nombre_projet, responsable, temps_execution, date_enregistrement FROM batch_traitement');
        $this->addSql('DROP TABLE batch_traitement');
        $this->addSql('CREATE TABLE batch_traitement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, demarrage VARCHAR(16) NOT NULL, resultat BOOLEAN NOT NULL, titre VARCHAR(32) NOT NULL, portefeuille VARCHAR(32) NOT NULL, nombre_projet INTEGER NOT NULL, responsable VARCHAR(128) NOT NULL, temp_execution INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('INSERT INTO batch_traitement (id, demarrage, resultat, titre, portefeuille, nombre_projet, responsable, temp_execution, date_enregistrement) SELECT id, demarrage, resultat, titre, portefeuille, nombre_projet, responsable, temps_execution, date_enregistrement FROM __temp__batch_traitement');
        $this->addSql('DROP TABLE __temp__batch_traitement');
    }
}
