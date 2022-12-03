<?php

declare(strict_types=1);

namespace MigrationsDefault;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221203200511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anomalie_details (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL, bug_blocker INTEGER NOT NULL, bug_critical INTEGER NOT NULL, bug_info INTEGER NOT NULL, bug_major INTEGER NOT NULL, bug_minor INTEGER NOT NULL, vulnerability_blocker INTEGER NOT NULL, vulnerability_critical INTEGER NOT NULL, vulnerability_info INTEGER NOT NULL, vulnerability_major INTEGER NOT NULL, vulnerability_minor INTEGER NOT NULL, code_smell_blocker INTEGER NOT NULL, code_smell_critical INTEGER NOT NULL, code_smell_info INTEGER NOT NULL, code_smell_major INTEGER NOT NULL, code_smell_minor INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE batch_traitement (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, demarrage VARCHAR(16) NOT NULL, resultat BOOLEAN NOT NULL, titre VARCHAR(32) NOT NULL, portefeuille VARCHAR(32) NOT NULL, nombre_projet INTEGER NOT NULL, responsable VARCHAR(128) NOT NULL, temp_execution INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anomalie_details');
        $this->addSql('DROP TABLE batch_traitement');
    }
}
