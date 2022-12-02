<?php

declare(strict_types=1);

namespace MigrationsDefault;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221202070121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE batch ADD COLUMN utilisateur VARCHAR(128) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__batch AS SELECT id, statut, nom, description, portefeuille, nombre_projet, date_modification, date_enregistrement FROM batch');
        $this->addSql('DROP TABLE batch');
        $this->addSql('CREATE TABLE batch (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, statut BOOLEAN NOT NULL, nom VARCHAR(32) NOT NULL, description VARCHAR(128) NOT NULL, portefeuille VARCHAR(32) NOT NULL, nombre_projet INTEGER NOT NULL, date_modification DATETIME DEFAULT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('INSERT INTO batch (id, statut, nom, description, portefeuille, nombre_projet, date_modification, date_enregistrement) SELECT id, statut, nom, description, portefeuille, nombre_projet, date_modification, date_enregistrement FROM __temp__batch');
        $this->addSql('DROP TABLE __temp__batch');
    }
}
