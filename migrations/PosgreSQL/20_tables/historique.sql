/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.3.0 - 27/03/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-03-27 : Les attributs concernant les metrics SonarQube sont nullable.
--- 2026-03-27 : Ajout des attributs par language/style pour les no_sonar et les to_do.
--- 2026-03-27 : Ajout des attributs SonarQube 10 et 2024, alignement des noms sur la version CORE (8 & 9).

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

    repartition_frontend INT,
    repartition_backend INT,
    repartition_autre INT,
    repartition_inconnu INT,

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

    alert_status VARCHAR(16),

    lines INT,
    ncloc INT,
    ncloc_language_distribution TEXT,
    classes INT,
    functions INT,
    files INT,
    statements INT,

    comment_lines INT,
    comment_lines_density DOUBLE PRECISION,
    comment_lines_rating VARCHAR(1),

    coverage DOUBLE PRECISION,
    branch_coverage DOUBLE PRECISION,
    line_coverage INT,
    lines_to_cover INT,
    conditions_to_cover INT,
    uncovered_conditions INT,

    tests INT,
    test_execution_time INT,
    test_errors INT,
    test_failures INT,
    skipped_tests INT,
    test_success_density float,

    duplicated_files INT,
    duplicated_blocks INT,
    duplicated_lines INT,
    duplicated_lines_density DOUBLE PRECISION,

    open_issues INT,
    reopened_issues INT,
    confirmed_issues INT,
    false_positive_issues INT,
    accepted_issues INT,
    high_impact_accepted_issues_issues INT,

    violations INT,
    blocker_violations INT,
    critical_violations INT,
    major_violations INT,
    minor_violations INT,
    info_violations INT,

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

    software_quality_blocker_issues INT,
    software_quality_high_issues INT,
    software_quality_medium_issues INT,
    software_quality_low_issues INT,
    software_quality_info_issues INT,

    complexity INT,
    cognitive_complexity INT,
    complexity_ratio DOUBLE PRECISION,
    cognitive_complexity_ratio DOUBLE PRECISION,
    cognitive_complexity_rating VARCHAR(1),

    code_smells INT,
    sqale_index INT,
    sqale_debt_ratio DOUBLE PRECISION,
    sqale_rating VARCHAR(1),
    maintainability_issues VARCHAR(255),
    software_quality_maintainability_issues VARCHAR(255),
    software_quality_maintainability_debt_ratio DOUBLE PRECISION,
    software_quality_maintainability_remediation_effort DOUBLE PRECISION,
    effort_to_reach_maintainability_rating_a VARCHAR(1),
    effort_to_reach_software_quality_maintainability_rating_a DOUBLE PRECISION,

    bug INT,
    reliability_rating VARCHAR(1),
    reliability_remediation_effort INT,
    reliability_issues VARCHAR(255),
    software_quality_reliability_issues VARCHAR(255),
    software_quality_reliability_rating VARCHAR(1),
    software_quality_reliability_remediation_effort INT,

    vulnerabilities INT,
    security_rating VARCHAR(1),
    security_remediation_effort INT,
    security_issues VARCHAR(255),
    software_quality_security_issues VARCHAR(255),
    software_quality_security_rating VARCHAR(1),
    software_quality_security_remediation_effort INT,

    security_hotspots INT,
    security_review_rating VARCHAR(1),
    security_hotspots_reviewed INT,

    menace_potentielle_to_review_high INT,
    menace_potentielle_to_review_medium INT,
    menace_potentielle_to_review_low INT,
    menace_potentielle_reviewed_high INT,
    menace_potentielle_reviewed_medium INT,
    menace_potentielle_reviewed_low INT,
    menace_potentielle_totale INT,

    initial BOOLEAN NOT NULL,

    mode_collecte VARCHAR(32),
    utilisateur_collecte VARCHAR(320),
    actuator_info JSON,

    logger_info INT,
    logger_warn INT,
    logger_error INT,
    logger_debug INT,

    date_enregistrement TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
