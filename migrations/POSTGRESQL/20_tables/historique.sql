/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.11.0 - 11/10/2026                 ##
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
--- 2026-04-26 : Ajustement de la taille de analyse_key
-- 2026-05-15 : breakdown level × framework (cf logger_details)
--- 2026-10-06 : Ajout des attributs scope_main, scope_test, bug_scope_main, bug_scope_test, vulnerability_scope_main, vulnerability_scope_test, code_smell_scope_main, code_smell_scope_test
--- 2026-10-08 : Ajout de la colone sonarqube_server_version pour tracer la version du serveur SonarQube ayant produit cette ligne.
--- 2026-10-08 : Ajout des attributs eco_anomalies, eco_dette_minute (Empreinte Écologique, plugins SonarQube Creedengo).
--- 2026-10-08 : Ajout de eco_rating et du détail par langage java/javascript/php/python (Empreinte Écologique).
--- 2026-10-11 : Ajout de maintainability_issue_severity, reliability_issue_severity, security_issue_severity (métriques optionnelles, pas encore LTS).
--- 2026-10-26 : Ajout des attributs accessibility_* (Accessibilité, tag SonarQube accessibility), globale et par langage css/web/javascript/typescript.

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette;

DROP TABLE IF EXISTS ma_moulinette.historique;

CREATE TABLE ma_moulinette.historique
(
    maven_key VARCHAR(255) NOT NULL,
    version VARCHAR(32) NOT NULL,
    date_version VARCHAR(128) NOT NULL,
    /* MODIF 2026-10-08 : version du serveur SonarQube ayant produit cette ligne. */
    sonarqube_server_version VARCHAR(32) NOT NULL DEFAULT 'inconnu',

    analyse_key VARCHAR(64) NOT NULL,
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
    -- MODIF 2026-05-06 : INT -> DOUBLE PRECISION (pourcentage)
    line_coverage DOUBLE PRECISION,
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

    /* MODIF 2026-10-06 : répartition par portée SonarQube (MAIN/TEST), globale et par catégorie */
    scope_main INT,
    scope_test INT,
    bug_scope_main INT,
    bug_scope_test INT,
    vulnerability_scope_main INT,
    vulnerability_scope_test INT,
    code_smell_scope_main INT,
    code_smell_scope_test INT,

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

    -- MODIF 2026-10-11 : métriques "optionnelles" apparues entre SonarQube 26.3.0
    -- et 26.9.0, pas encore documentées/LTS — valeur brute, pas de note A-E.
    maintainability_issue_severity INT,
    reliability_issue_severity INT,
    security_issue_severity INT,

    menace_potentielle_to_review_high INT,
    menace_potentielle_to_review_medium INT,
    menace_potentielle_to_review_low INT,
    menace_potentielle_reviewed_high INT,
    menace_potentielle_reviewed_medium INT,
    menace_potentielle_reviewed_low INT,
    menace_potentielle_totale INT,

    actuator_info JSON,
    logger_breakdown JSONB,

    logger_info INT,
    logger_warn INT,
    logger_error INT,
    logger_debug INT,

    /* MODIF 2026-05-17 : indicateurs SonarQube 10+ */
    cc_consistent INT,
    cc_intentional INT,
    cc_adaptable INT,
    cc_responsible INT,
    quality_maintainability INT,
    quality_reliability INT,
    quality_security INT,
    impact_blocker INT,
    impact_high INT,
    impact_medium INT,
    impact_low INT,
    impact_info INT,
    owasp_top10 INT,
    sans_top25 INT,
    cwe INT,

    /* MODIF 2026-09-25 : RCI (Risk Criticality Index, plugin sonar-rci porté en PHP) */
    rci_ncloc INT,
    rci_weight INT,
    rci_indicator DOUBLE PRECISION,
    rci_rating VARCHAR(4),
    rci_frontend_indicator DOUBLE PRECISION,
    rci_frontend_rating VARCHAR(4),
    rci_frontend_ncloc INT,
    rci_frontend_weight INT,
    rci_backend_indicator DOUBLE PRECISION,
    rci_backend_rating VARCHAR(4),
    rci_backend_ncloc INT,
    rci_backend_weight INT,
    rci_autre_indicator DOUBLE PRECISION,
    rci_autre_rating VARCHAR(4),
    rci_autre_ncloc INT,
    rci_autre_weight INT,
    rci_inconnu_indicator DOUBLE PRECISION,
    rci_inconnu_rating VARCHAR(4),
    rci_inconnu_ncloc INT,
    rci_inconnu_weight INT,

    eco_anomalies INT,
    eco_dette_minute INT,
    eco_rating VARCHAR(4),
    eco_java_anomalies INT,
    eco_java_dette_minute INT,
    eco_java_rating VARCHAR(4),
    eco_javascript_anomalies INT,
    eco_javascript_dette_minute INT,
    eco_javascript_rating VARCHAR(4),
    eco_php_anomalies INT,
    eco_php_dette_minute INT,
    eco_php_rating VARCHAR(4),
    eco_python_anomalies INT,
    eco_python_dette_minute INT,
    eco_python_rating VARCHAR(4),

    accessibility_anomalies INT,
    accessibility_dette_minute INT,
    accessibility_rating VARCHAR(4),
    accessibility_css_anomalies INT,
    accessibility_css_dette_minute INT,
    accessibility_css_rating VARCHAR(4),
    accessibility_web_anomalies INT,
    accessibility_web_dette_minute INT,
    accessibility_web_rating VARCHAR(4),
    accessibility_javascript_anomalies INT,
    accessibility_javascript_dette_minute INT,
    accessibility_javascript_rating VARCHAR(4),
    accessibility_typescript_anomalies INT,
    accessibility_typescript_dette_minute INT,
    accessibility_typescript_rating VARCHAR(4),

    initial BOOLEAN NOT NULL,

    mode_collecte VARCHAR(32),
    utilisateur_collecte VARCHAR(320),
    date_enregistrement TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
