/*
####################################################
##                                                ##
##           Create VIEWS                         ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette;

DROP VIEW IF EXISTS ma_moulinette.vw_batch_profiling_weekly;

CREATE OR REPLACE VIEW ma_moulinette.vw_batch_profiling_weekly AS
SELECT
    bp.portefeuille,
    bp.utilisateur,
    DATE_TRUNC('week', bp.date_execution)::DATE AS semaine,
    COUNT(bp.id) AS nb_executions,
    SUM(bp.nb_projets) AS total_projets,
    ROUND(AVG(bp.temps_total)::numeric, 2) AS temps_total_moyen_s,
    ROUND(AVG(bp.temps_moyen)::numeric, 2) AS temps_moyen_projet_s,
    ROUND(AVG(bp.memoire_peak)::numeric, 2) AS memoire_peak_moyenne_mo,
    ROUND(AVG(bp.memoire_moyenne)::numeric, 2) AS memoire_moyenne_mo,
    ROUND(MAX(bp.memoire_peak)::numeric, 2) AS memoire_peak_max_mo
FROM ma_moulinette.batch_profiling bp
GROUP BY
    bp.portefeuille,
    bp.utilisateur,
    DATE_TRUNC('week', bp.date_execution)
ORDER BY
    semaine DESC, bp.portefeuille, bp.utilisateur;

ALTER VIEW ma_moulinette.vw_batch_profiling_weekly OWNER TO db_user;
GRANT SELECT ON ma_moulinette.vw_batch_profiling_weekly TO db_user;
