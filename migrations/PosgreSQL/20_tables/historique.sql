/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.2.0 - 27/03/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-03-27 : Les attributs concernant les metrics SonarQube sont nullable.
--- 2026-03-27 : Ajout des attributs par language/style pour les no_sonar et les to_do.

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

    version_release INT,
    version_snapshot INT,
    version_autre INT,

    java_no_sonar INT,
    python_no_sonar INT,
    php_no_sonar INT,
    suppress_warning INT,
    no_pmd INT,
    check_style INT,

    java_todo INT,
    python_todo INT,
    php_todo INT,
    xml_todo INT,
    web_todo INT,
    javascript_todo INT,
    typescript_todo INT,
    ruby_todo INT,

    nombre_ligne INT,
    nombre_ligne_code INT,
    nombre_classes INT,
    nombre_functions INT,
    nombre_files INT,

    coverage DOUBLE PRECISION,
    duplicated_lines_density DOUBLE PRECISION,

    tests INT,
    violations INT,

    nombre_bug INT,
    nombre_vulnerability INT,
    nombre_code_smell INT,

    frontend INT,
    backend INT,
    autre INT,
    inconnu INT,

    dette INT,
    sqale_debt_ratio DOUBLE PRECISION,

    nombre_anomalie_bloquant INT,
    nombre_anomalie_critique INT,
    nombre_anomalie_info INT,
    nombre_anomalie_majeur INT,
    nombre_anomalie_mineur INT,

    note_reliability VARCHAR(16),
    note_security VARCHAR(16),
    note_sqale VARCHAR(16),
    note_hotspot VARCHAR(16),

    menace_potentielle_to_review_high INT,
    menace_potentielle_to_review_medium INT,
    menace_potentielle_to_review_low INT,
    menace_potentielle_reviewed_high INT,
    menace_potentielle_reviewed_medium INT,
    menace_potentielle_reviewed_low INT,
    menace_potentielle_totale INT,

    initial BOOLEAN NOT NULL,

    bug_blocker INT,
    bug_critical INT,
    bug_major INT,
    bug_minor INT,
    bug_info INT,

    vulnerability_blocker INT,
    vulnerability_critical INT,
    vulnerability_major INT,
    vulnerability_minor INT,
    vulnerability_info INT,

    code_smell_blocker INT,
    code_smell_critical INT,
    code_smell_major INT,
    code_smell_minor INT,
    code_smell_info INT,

    mode_collecte VARCHAR(32),
    utilisateur_collecte VARCHAR(320),
    actuator_info JSON,

    logger_info INT,
    logger_warn INT,
    logger_error INT,
    logger_debug INT,

    date_enregistrement TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
