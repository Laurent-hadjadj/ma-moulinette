/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette db_user;

DROP TABLE IF EXISTS ma_moulinette.historique;

CREATE TABLE ma_moulinette.historique
(
    maven_key VARCHAR(255) NOT NULL,
    version VARCHAR(32) NOT NULL,
    date_version VARCHAR(128) NOT NULL,

    analyse_key VARCHAR(32) NOT NULL,
    nom_projet VARCHAR(128) NOT NULL,

    version_release INT NOT NULL,
    version_snapshot INT NOT NULL,
    version_autre INT NOT NULL,

    suppress_warning INT NOT NULL,
    no_sonar INT NOT NULL,
    todo INT NOT NULL,

    nombre_ligne INT NOT NULL,
    nombre_ligne_code INT NOT NULL,
    nombre_classes INT NOT NULL,
    nombre_functions INT NOT NULL,
    nombre_files INT NOT NULL,

    coverage DOUBLE PRECISION NOT NULL,
    duplicated_lines_density DOUBLE PRECISION NOT NULL,

    tests INT NOT NULL,
    violations INT NOT NULL,

    nombre_bug INT NOT NULL,
    nombre_vulnerability INT NOT NULL,
    nombre_code_smell INT NOT NULL,

    frontend INT NOT NULL,
    backend INT NOT NULL,
    autre INT NOT NULL,
    inconnu INT NOT NULL,

    dette INT NOT NULL,
    sqale_debt_ratio DOUBLE PRECISION NOT NULL,

    nombre_anomalie_bloquant INT NOT NULL,
    nombre_anomalie_critique INT NOT NULL,
    nombre_anomalie_info INT NOT NULL,
    nombre_anomalie_majeur INT NOT NULL,
    nombre_anomalie_mineur INT NOT NULL,

    note_reliability VARCHAR(16) NOT NULL,
    note_security VARCHAR(16) NOT NULL,
    note_sqale VARCHAR(16) NOT NULL,
    note_hotspot VARCHAR(16) NOT NULL,

    menace_potentielle_to_review_high INT NOT NULL,
    menace_potentielle_to_review_medium INT NOT NULL,
    menace_potentielle_to_review_low INT NOT NULL,
    menace_potentielle_reviewed_high INT NOT NULL,
    menace_potentielle_reviewed_medium INT NOT NULL,
    menace_potentielle_reviewed_low INT NOT NULL,
    menace_potentielle_totale INT NOT NULL,

    initial BOOLEAN NOT NULL,

    bug_blocker INT NOT NULL,
    bug_critical INT NOT NULL,
    bug_major INT NOT NULL,
    bug_minor INT NOT NULL,
    bug_info INT NOT NULL,

    vulnerability_blocker INT NOT NULL,
    vulnerability_critical INT NOT NULL,
    vulnerability_major INT NOT NULL,
    vulnerability_minor INT NOT NULL,
    vulnerability_info INT NOT NULL,

    code_smell_blocker INT NOT NULL,
    code_smell_critical INT NOT NULL,
    code_smell_major INT NOT NULL,
    code_smell_minor INT NOT NULL,
    code_smell_info INT NOT NULL,

    mode_collecte VARCHAR(32),
    utilisateur_collecte VARCHAR(320),
    actuator_info JSON,

    logger_info INT,
    logger_warn INT,
    logger_error INT,
    logger_debug INT,

    date_enregistrement TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
);
