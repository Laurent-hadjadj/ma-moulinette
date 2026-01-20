/*
####################################################
##                                                ##
##           Create VIEWS                         ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette db_user

DROP VIEW IF EXISTS ma_moulinette.vw_batch_profiling_summary;

CREATE OR REPLACE VIEW ma_moulinette.vw_batch_profiling_summary AS

-- 1. Niveau hebdomadaire
SELECT
    'Hebdomadaire'::text AS granularite,
    portefeuille,
    utilisateur,
    TO_CHAR(semaine, 'IYYY-"S"IW') AS periode,
    nb_executions AS nb_exec,
    total_projets AS nb_projets,
    temps_total_moyen_s,
    temps_moyen_projet_s,
    memoire_peak_moyenne_mo,
    memoire_moyenne_mo,
    memoire_peak_max_mo,
    NULL::timestamp AS premiere_execution,
    NULL::timestamp AS derniere_execution
FROM ma_moulinette.vw_batch_profiling_weekly

UNION ALL

-- 2. Niveau mensuel
SELECT
    'Mensuel'::text AS granularite,
    portefeuille,
    utilisateur,
    mois AS periode,
    nb_executions AS nb_exec,
    total_projets AS nb_projets,
    temps_total_moyen_s,
    temps_moyen_projet_s,
    memoire_peak_moyenne_mo,
    memoire_moyenne_mo,
    memoire_peak_max_mo,
    NULL::timestamp AS premiere_execution,
    derniere_execution
FROM ma_moulinette.vw_batch_profiling_monthly

UNION ALL

-- 3. Niveau global
SELECT
    'Global'::text AS granularite,
    portefeuille,
    utilisateur,
    'Historique complet'::text AS periode,
    nb_executions_total AS nb_exec,
    total_projets AS nb_projets,
    temps_total_moyen_s,
    temps_moyen_projet_s,
    memoire_peak_moyenne_mo,
    memoire_moyenne_mo,
    memoire_peak_max_mo,
    premiere_execution,
    derniere_execution
FROM ma_moulinette.vw_batch_profiling_global;

ALTER VIEW ma_moulinette.vw_batch_profiling_summary OWNER TO db_user;
GRANT SELECT ON ma_moulinette.vw_batch_profiling_summary TO db_user;
