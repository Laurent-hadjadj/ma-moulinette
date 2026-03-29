/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.5.2 - 29/03/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-03-27 : Les attributs concernant les metrics SonarQube sont nullable.
--- 2026-03-27 : Ajout des attributs par language/style pour les no_sonar et les to_do.
--- 2026-03-27 : Ajout des attributs SonarQube 10 et 2024, alignement des noms sur la version CORE (8 & 9).
--- 2026-03-28 : Corrections sur les types et réorganisation.
--- 2026-03-30 : Renommage nom_projet en project_name.
--- 2026-03-30 : Ajout de coverage_rating.
--- 2026-03-30 : Ajout des attributs duplicated_lines_rating.

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette db_user;

DROP TABLE IF EXISTS ma_moulinette.historique;

CREATE TABLE ma_moulinette.historique
(
    maven_key VARCHAR(255) NOT NULL,
    version VARCHAR(32) NOT NULL,
    date_version VARCHAR(128) NOT NULL,

    analyse_key VARCHAR(32) NOT NULL,
    project_name VARCHAR(128) NOT NULL,

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
    files INT,
    classes INT,
    functions INT,
    statements INT,

    comment_lines INT,
    comment_lines_density DOUBLE PRECISION,
    comment_lines_rating VARCHAR(4),

    coverage DOUBLE PRECISION,
    branch_coverage DOUBLE PRECISION,
    coverage_rating VARCHAR(4),
    line_coverage INT,
    lines_to_cover INT,
    conditions_to_cover INT,
    uncovered_conditions INT,

    tests INT,
    test_execution_time INT,
    test_errors INT,
    test_failures INT,
    skipped_tests INT,
    test_success_density DOUBLE PRECISION,

    duplicated_files INT,
    duplicated_blocks INT,
    duplicated_lines INT,
    duplicated_lines_density DOUBLE PRECISION,
    duplicated_lines_rating VARCHAR(4),

    complexity INT,
    complexity_rating VARCHAR(4),
    cognitive_complexity INT,
    cognitive_complexity_rating VARCHAR(4),
    complexity_ratio DOUBLE PRECISION,
    cognitive_complexity_ratio DOUBLE PRECISION,

    open_issues INT,
    reopened_issues INT,
    confirmed_issues INT,
    false_positive_issues INT,
    accepted_issues INT,
    high_impact_accepted_issues INT,

    violations INT,
    blocker_violations INT,
    critical_violations INT,
    major_violations INT,
    minor_violations INT,
    info_violations INT,

    software_quality_blocker_issues INT,
    software_quality_high_issues INT,
    software_quality_medium_issues INT,
    software_quality_low_issues INT,
    software_quality_info_issues INT,

    code_smells INT,
    code_smell_blocker INT,
    code_smell_critical INT,
    code_smell_major INT,
    code_smell_minor INT,
    code_smell_info INT,

    maintainability_issues VARCHAR(255),
    sqale_index INT,
    sqale_debt_ratio DOUBLE PRECISION,
    sqale_rating VARCHAR(4),
    effort_to_reach_maintainability_rating_a INT,

    software_quality_maintainability_issues VARCHAR(255),
    software_quality_maintainability_rating VARCHAR(4),
    software_quality_maintainability_debt_ratio DOUBLE PRECISION,
    software_quality_maintainability_remediation_effort INTEGER,
    effort_to_reach_software_quality_maintainability_rating_a INTEGER,

    bugs INT,
    bug_blocker INT,
    bug_critical INT,
    bug_major INT,
    bug_minor INT,
    bug_info INT,

    reliability_issues VARCHAR(255),
    reliability_rating VARCHAR(4),
    reliability_remediation_effort INT,

    software_quality_reliability_issues VARCHAR(255),
    software_quality_reliability_rating VARCHAR(4),
    software_quality_reliability_remediation_effort INT,

    vulnerabilities INT,
    vulnerability_blocker INT,
    vulnerability_critical INT,
    vulnerability_major INT,
    vulnerability_minor INT,
    vulnerability_info INT,

    security_issues VARCHAR(255),
    security_rating VARCHAR(4),
    security_remediation_effort INT,

    software_quality_security_issues VARCHAR(255),
    software_quality_security_rating VARCHAR(4),
    software_quality_security_remediation_effort INT,

    security_hotspots INT,
    security_review_rating VARCHAR(4),
    security_hotspots_reviewed DOUBLE PRECISION,

    menace_potentielle_to_review_high INT,
    menace_potentielle_to_review_medium INT,
    menace_potentielle_to_review_low INT,
    menace_potentielle_reviewed_high INT,
    menace_potentielle_reviewed_medium INT,
    menace_potentielle_reviewed_low INT,
    menace_potentielle_totale INT,

    actuator_info JSON,

    logger_info INT,
    logger_warn INT,
    logger_error INT,
    logger_debug INT,

    initial BOOLEAN NOT NULL,

    mode_collecte VARCHAR(32),
    utilisateur_collecte VARCHAR(320),
    date_enregistrement TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
