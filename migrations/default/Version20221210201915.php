<?php

declare(strict_types=1);

namespace MigrationsDefault;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221210201915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique ADD COLUMN version_autre INTEGER NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__historique AS SELECT maven_key, version, date_version, nom_projet, version_release, version_snapshot, suppress_warning, no_sonar, nombre_ligne, nombre_ligne_code, couverture, duplication, tests_unitaires, nombre_defaut, nombre_bug, nombre_vulnerability, nombre_code_smell, frontend, backend, autre, dette, nombre_anomalie_bloquant, nombre_anomalie_critique, nombre_anomalie_info, nombre_anomalie_majeur, nombre_anomalie_mineur, note_reliability, note_security, note_sqale, note_hotspot, hotspot_high, hotspot_medium, hotspot_low, hotspot_total, favori, initial, bug_blocker, bug_critical, bug_major, bug_minor, bug_info, vulnerability_blocker, vulnerability_critical, vulnerability_major, vulnerability_minor, vulnerability_info, code_smell_blocker, code_smell_critical, code_smell_major, code_smell_minor, code_smell_info, date_enregistrement FROM historique');
        $this->addSql('DROP TABLE historique');
        $this->addSql('CREATE TABLE historique (maven_key VARCHAR(128) NOT NULL, version VARCHAR(32) NOT NULL, date_version VARCHAR(128) NOT NULL, nom_projet VARCHAR(128) NOT NULL, version_release INTEGER NOT NULL, version_snapshot INTEGER NOT NULL, suppress_warning INTEGER NOT NULL, no_sonar INTEGER NOT NULL, nombre_ligne INTEGER NOT NULL, nombre_ligne_code INTEGER NOT NULL, couverture DOUBLE PRECISION NOT NULL, duplication DOUBLE PRECISION NOT NULL, tests_unitaires INTEGER NOT NULL, nombre_defaut INTEGER NOT NULL, nombre_bug INTEGER NOT NULL, nombre_vulnerability INTEGER NOT NULL, nombre_code_smell INTEGER NOT NULL, frontend INTEGER NOT NULL, backend INTEGER NOT NULL, autre INTEGER NOT NULL, dette INTEGER NOT NULL, nombre_anomalie_bloquant INTEGER NOT NULL, nombre_anomalie_critique INTEGER NOT NULL, nombre_anomalie_info INTEGER NOT NULL, nombre_anomalie_majeur INTEGER NOT NULL, nombre_anomalie_mineur INTEGER NOT NULL, note_reliability VARCHAR(4) NOT NULL, note_security VARCHAR(4) NOT NULL, note_sqale VARCHAR(4) NOT NULL, note_hotspot VARCHAR(4) NOT NULL, hotspot_high INTEGER NOT NULL, hotspot_medium INTEGER NOT NULL, hotspot_low INTEGER NOT NULL, hotspot_total INTEGER NOT NULL, favori BOOLEAN NOT NULL, initial BOOLEAN NOT NULL, bug_blocker INTEGER NOT NULL, bug_critical INTEGER NOT NULL, bug_major INTEGER NOT NULL, bug_minor INTEGER NOT NULL, bug_info INTEGER NOT NULL, vulnerability_blocker INTEGER NOT NULL, vulnerability_critical INTEGER NOT NULL, vulnerability_major INTEGER NOT NULL, vulnerability_minor INTEGER NOT NULL, vulnerability_info INTEGER NOT NULL, code_smell_blocker INTEGER NOT NULL, code_smell_critical INTEGER NOT NULL, code_smell_major INTEGER NOT NULL, code_smell_minor INTEGER NOT NULL, code_smell_info INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL, PRIMARY KEY(maven_key, version, date_version))');
        $this->addSql('INSERT INTO historique (maven_key, version, date_version, nom_projet, version_release, version_snapshot, suppress_warning, no_sonar, nombre_ligne, nombre_ligne_code, couverture, duplication, tests_unitaires, nombre_defaut, nombre_bug, nombre_vulnerability, nombre_code_smell, frontend, backend, autre, dette, nombre_anomalie_bloquant, nombre_anomalie_critique, nombre_anomalie_info, nombre_anomalie_majeur, nombre_anomalie_mineur, note_reliability, note_security, note_sqale, note_hotspot, hotspot_high, hotspot_medium, hotspot_low, hotspot_total, favori, initial, bug_blocker, bug_critical, bug_major, bug_minor, bug_info, vulnerability_blocker, vulnerability_critical, vulnerability_major, vulnerability_minor, vulnerability_info, code_smell_blocker, code_smell_critical, code_smell_major, code_smell_minor, code_smell_info, date_enregistrement) SELECT maven_key, version, date_version, nom_projet, version_release, version_snapshot, suppress_warning, no_sonar, nombre_ligne, nombre_ligne_code, couverture, duplication, tests_unitaires, nombre_defaut, nombre_bug, nombre_vulnerability, nombre_code_smell, frontend, backend, autre, dette, nombre_anomalie_bloquant, nombre_anomalie_critique, nombre_anomalie_info, nombre_anomalie_majeur, nombre_anomalie_mineur, note_reliability, note_security, note_sqale, note_hotspot, hotspot_high, hotspot_medium, hotspot_low, hotspot_total, favori, initial, bug_blocker, bug_critical, bug_major, bug_minor, bug_info, vulnerability_blocker, vulnerability_critical, vulnerability_major, vulnerability_minor, vulnerability_info, code_smell_blocker, code_smell_critical, code_smell_major, code_smell_minor, code_smell_info, date_enregistrement FROM __temp__historique');
        $this->addSql('DROP TABLE __temp__historique');
    }
}
