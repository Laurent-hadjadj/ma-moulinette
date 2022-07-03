<?php

declare(strict_types=1);

namespace MigrationsDefault;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220703002750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE anomalie (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, project_name VARCHAR(128) NOT NULL, anomalie_total INTEGER NOT NULL, dette_minute INTEGER NOT NULL, dette_reliability_minute INTEGER NOT NULL, dette_vulnerability_minute INTEGER NOT NULL, dette_code_smell_minute INTEGER NOT NULL, dette_reliability VARCHAR(32) NOT NULL, dette_vulnerability VARCHAR(32) NOT NULL, dette VARCHAR(32) NOT NULL, dette_code_smell VARCHAR(32) NOT NULL, frontend INTEGER NOT NULL, backend INTEGER NOT NULL, autre INTEGER NOT NULL, blocker INTEGER NOT NULL, critical INTEGER NOT NULL, major INTEGER NOT NULL, info INTEGER NOT NULL, minor INTEGER NOT NULL, bug INTEGER NOT NULL, vulnerability INTEGER NOT NULL, code_smell INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE anomalie_details (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL, bug_blocker INTEGER NOT NULL, bug_critical INTEGER NOT NULL, bug_info INTEGER NOT NULL, bug_major INTEGER NOT NULL, bug_minor INTEGER NOT NULL, vulnerability_blocker INTEGER NOT NULL, vulnerability_critical INTEGER NOT NULL, vulnerability_info INTEGER NOT NULL, vulnerability_major INTEGER NOT NULL, vulnerability_minor INTEGER NOT NULL, code_smell_blocker INTEGER NOT NULL, code_smell_critical INTEGER NOT NULL, code_smell_info INTEGER NOT NULL, code_smell_major INTEGER NOT NULL, code_smell_minor INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE favori (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, favori BOOLEAN NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE historique (maven_key VARCHAR(128) NOT NULL, version VARCHAR(32) NOT NULL, date_version VARCHAR(128) NOT NULL, nom_projet VARCHAR(128) NOT NULL, version_release INTEGER NOT NULL, version_snapshot INTEGER NOT NULL, suppress_warning INTEGER NOT NULL, no_sonar INTEGER NOT NULL, nombre_ligne INTEGER NOT NULL, nombre_ligne_code INTEGER NOT NULL, couverture DOUBLE PRECISION NOT NULL, duplication DOUBLE PRECISION NOT NULL, tests_unitaires INTEGER NOT NULL, nombre_defaut INTEGER NOT NULL, nombre_bug INTEGER NOT NULL, nombre_vulnerability INTEGER NOT NULL, nombre_code_smell INTEGER NOT NULL, frontend INTEGER NOT NULL, backend INTEGER NOT NULL, autre INTEGER NOT NULL, dette INTEGER NOT NULL, nombre_anomalie_bloquant INTEGER NOT NULL, nombre_anomalie_critique INTEGER NOT NULL, nombre_anomalie_info INTEGER NOT NULL, nombre_anomalie_majeur INTEGER NOT NULL, nombre_anomalie_mineur INTEGER NOT NULL, note_reliability VARCHAR(4) NOT NULL, note_security VARCHAR(4) NOT NULL, note_sqale VARCHAR(4) NOT NULL, note_hotspot VARCHAR(4) NOT NULL, hotspot_high VARCHAR(4) NOT NULL, hotspot_medium INTEGER NOT NULL, hotspot_low INTEGER NOT NULL, hotspot_total INTEGER NOT NULL, favori BOOLEAN NOT NULL, initial BOOLEAN NOT NULL, bug_blocker INTEGER NOT NULL, bug_critical INTEGER NOT NULL, bug_major INTEGER NOT NULL, bug_minor INTEGER NOT NULL, bug_info INTEGER NOT NULL, vulnerability_blocker INTEGER NOT NULL, vulnerability_critical INTEGER NOT NULL, vulnerability_major INTEGER NOT NULL, vulnerability_minor INTEGER NOT NULL, vulnerability_info INTEGER NOT NULL, code_smell_blocker INTEGER NOT NULL, code_smell_critical INTEGER NOT NULL, code_smell_major INTEGER NOT NULL, code_smell_minor INTEGER NOT NULL, code_smell_info INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL, PRIMARY KEY(maven_key, version, date_version))');
        $this->addSql('CREATE TABLE hotspot_details (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, severity VARCHAR(8) NOT NULL, niveau INTEGER NOT NULL, status VARCHAR(16) NOT NULL, frontend INTEGER NOT NULL, backend INTEGER NOT NULL, autre INTEGER NOT NULL, file VARCHAR(255) NOT NULL, line INTEGER NOT NULL, rule VARCHAR(255) NOT NULL, message VARCHAR(255) NOT NULL, "key" VARCHAR(32) NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE hotspot_owasp (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, menace VARCHAR(8) NOT NULL, probability VARCHAR(8) NOT NULL, status VARCHAR(16) NOT NULL, niveau INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE hotspots (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, "key" VARCHAR(32) NOT NULL, probability VARCHAR(8) NOT NULL, status VARCHAR(16) NOT NULL, niveau INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE information_projet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, analyse_key VARCHAR(32) NOT NULL, date DATETIME NOT NULL, project_version VARCHAR(32) NOT NULL, type VARCHAR(32) NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE liste_projet (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE mesures (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, project_name VARCHAR(128) NOT NULL, lines INTEGER NOT NULL, ncloc INTEGER NOT NULL, coverage DOUBLE PRECISION NOT NULL, duplication_density DOUBLE PRECISION NOT NULL, tests INTEGER NOT NULL, issues INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE no_sonar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, rule VARCHAR(128) NOT NULL, component CLOB NOT NULL, line INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE notes (maven_key VARCHAR(128) NOT NULL, type VARCHAR(16) NOT NULL, date DATETIME NOT NULL, value INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL, PRIMARY KEY(maven_key, type, date))');
        $this->addSql('CREATE TABLE owasp (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, effort_total INTEGER NOT NULL, a1 INTEGER NOT NULL, a2 INTEGER NOT NULL, a3 INTEGER NOT NULL, a4 INTEGER NOT NULL, a5 INTEGER NOT NULL, a6 INTEGER NOT NULL, a7 INTEGER NOT NULL, a8 INTEGER NOT NULL, a9 INTEGER NOT NULL, a10 INTEGER NOT NULL, a1_blocker INTEGER NOT NULL, a1_critical INTEGER NOT NULL, a1_major INTEGER NOT NULL, a1_info INTEGER NOT NULL, a1_minor INTEGER NOT NULL, a2_blocker INTEGER NOT NULL, a2_critical INTEGER NOT NULL, a2_major INTEGER NOT NULL, a2_info INTEGER NOT NULL, a2_minor INTEGER NOT NULL, a3_blocker INTEGER NOT NULL, a3_critical INTEGER NOT NULL, a3_major INTEGER NOT NULL, a3_info INTEGER NOT NULL, a3_minor INTEGER NOT NULL, a4_blocker INTEGER NOT NULL, a4_critical INTEGER NOT NULL, a4_major INTEGER NOT NULL, a4_info INTEGER NOT NULL, a4_minor INTEGER NOT NULL, a5_blocker INTEGER NOT NULL, a5_critical INTEGER NOT NULL, a5_major INTEGER NOT NULL, a5_info INTEGER NOT NULL, a5_minor INTEGER NOT NULL, a6_blocker INTEGER NOT NULL, a6_critical INTEGER NOT NULL, a6_major INTEGER NOT NULL, a6_info INTEGER NOT NULL, a6_minor INTEGER NOT NULL, a7_blocker INTEGER NOT NULL, a7_critical INTEGER NOT NULL, a7_major INTEGER NOT NULL, a7_info INTEGER NOT NULL, a7_minor INTEGER NOT NULL, a8_blocker INTEGER NOT NULL, a8_critical INTEGER NOT NULL, a8_major INTEGER NOT NULL, a8_info INTEGER NOT NULL, a8_minor INTEGER NOT NULL, a9_blocker INTEGER NOT NULL, a9_critical INTEGER NOT NULL, a9_major INTEGER NOT NULL, a9_info INTEGER NOT NULL, a9_minor INTEGER NOT NULL, a10_blocker INTEGER NOT NULL, a10_critical INTEGER NOT NULL, a10_major INTEGER NOT NULL, a10_info INTEGER NOT NULL, a10_minor INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE profiles (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, "key" VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL, language_name VARCHAR(64) NOT NULL, active_rule_count INTEGER NOT NULL, rules_update_at DATETIME NOT NULL, is_default BOOLEAN NOT NULL, date_enregistrement DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE anomalie');
        $this->addSql('DROP TABLE anomalie_details');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE historique');
        $this->addSql('DROP TABLE hotspot_details');
        $this->addSql('DROP TABLE hotspot_owasp');
        $this->addSql('DROP TABLE hotspots');
        $this->addSql('DROP TABLE information_projet');
        $this->addSql('DROP TABLE liste_projet');
        $this->addSql('DROP TABLE mesures');
        $this->addSql('DROP TABLE no_sonar');
        $this->addSql('DROP TABLE notes');
        $this->addSql('DROP TABLE owasp');
        $this->addSql('DROP TABLE profiles');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
