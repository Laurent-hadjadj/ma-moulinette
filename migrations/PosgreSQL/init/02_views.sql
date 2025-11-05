/*
####################################################
##                                                ##
##         Creation des tables et des objets      ##
##               V1.0.0 - 02/11/2025              ##
##                                                ##
####################################################*/

/* #### Le script doit être lancé avec l'utilisateur propriétaire de la base, ici db_user #### */

-- SCHEMA: ma_moulinette

-- ===============================================
-- VIEW: ma_moulinette.vw_batch_profiling_stats
-- ===============================================

DROP VIEW IF EXISTS ma_moulinette.vw_batch_profiling_stats;
CREATE OR REPLACE VIEW ma_moulinette.vw_batch_profiling_stats AS
SELECT
    bp.portefeuille,
    bp.utilisateur,
    COUNT(bp.id) AS nb_executions,
    SUM(bp.nb_projets) AS total_projets,
    ROUND(AVG(bp.temps_total)::numeric, 2) AS temps_total_moyen_s,
    ROUND(AVG(bp.temps_moyen)::numeric, 2) AS temps_moyen_projet_s,
    ROUND(AVG(bp.memoire_peak)::numeric, 2) AS memoire_peak_moyenne_mo,
    ROUND(AVG(bp.memoire_moyenne)::numeric, 2) AS memoire_moyenne_mo,
    ROUND(MAX(bp.memoire_peak)::numeric, 2) AS memoire_peak_max_mo,
    MAX(bp.date_execution) AS derniere_execution
FROM ma_moulinette.batch_profiling bp
GROUP BY bp.portefeuille, bp.utilisateur
ORDER BY derniere_execution DESC;

ALTER VIEW ma_moulinette.vw_batch_profiling_stats OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.utilisateur TO db_user;

COMMENT ON VIEW ma_moulinette.vw_batch_profiling_stats IS 'Vue d’analyse consolidée des statistiques de performance des traitements manuels et automatiques.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.portefeuille IS 'Nom du portefeuille (ex: LOT-3, JAVA, etc.)';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.utilisateur IS 'Adresse de l’utilisateur ayant déclenché les traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.nb_executions IS 'Nombre total d’exécutions pour ce portefeuille/utilisateur.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.total_projets IS 'Nombre total de projets traités dans toutes les exécutions.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.temps_total_moyen_s IS 'Temps moyen total par exécution, en secondes.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.temps_moyen_projet_s IS 'Temps moyen par projet, en secondes.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.memoire_peak_moyenne_mo IS 'Pic mémoire moyen (Mo) observé sur toutes les exécutions.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.memoire_moyenne_mo IS 'Mémoire moyenne par projet (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.memoire_peak_max_mo IS 'Plus haut pic mémoire atteint pour ce portefeuille/utilisateur.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.derniere_execution IS
'Horodatage de la dernière exécution enregistrée pour ce portefeuille/utilisateur.';

-- ===============================================
-- VIEW: ma_moulinette.vw_batch_profiling_weekly
-- ===============================================

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
GRANT ALL ON TABLE ma_moulinette.utilisateur TO db_user;

COMMENT ON VIEW ma_moulinette.vw_batch_profiling_weekly IS 'Vue de synthèse hebdomadaire des performances des batchs (temps moyen, mémoire, nombre d’exécutions).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.portefeuille IS 'Nom du portefeuille traité.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.utilisateur IS 'Utilisateur à l’origine des traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.semaine IS 'Date de début de semaine (lundi).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.nb_executions IS 'Nombre total d’exécutions durant la semaine.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.total_projets IS 'Nombre total de projets analysés durant la semaine.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.temps_total_moyen_s IS 'Temps moyen total d’exécution pour la semaine, en secondes.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.memoire_peak_moyenne_mo IS 'Pic mémoire moyen hebdomadaire (en Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.memoire_moyenne_mo IS 'Mémoire moyenne utilisée par projet durant la semaine (en Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.memoire_peak_max_mo IS 'Pic mémoire le plus haut observé durant la semaine.';

-- ===============================================
-- VIEW: ma_moulinette.vw_batch_profiling_monthly
-- ===============================================

DROP VIEW IF EXISTS ma_moulinette.vw_batch_profiling_monthly;
CREATE OR REPLACE VIEW ma_moulinette.vw_batch_profiling_monthly AS
SELECT
    bp.portefeuille,
    bp.utilisateur,
    TO_CHAR(DATE_TRUNC('month', bp.date_execution), 'YYYY-MM') AS mois,
    COUNT(bp.id) AS nb_executions,
    SUM(bp.nb_projets) AS total_projets,
    ROUND(AVG(bp.temps_total)::numeric, 2) AS temps_total_moyen_s,
    ROUND(AVG(bp.temps_moyen)::numeric, 2) AS temps_moyen_projet_s,
    ROUND(AVG(bp.memoire_peak)::numeric, 2) AS memoire_peak_moyenne_mo,
    ROUND(AVG(bp.memoire_moyenne)::numeric, 2) AS memoire_moyenne_mo,
    ROUND(MAX(bp.memoire_peak)::numeric, 2) AS memoire_peak_max_mo,
    MAX(bp.date_execution) AS derniere_execution
FROM ma_moulinette.batch_profiling bp
GROUP BY
    bp.portefeuille,
    bp.utilisateur,
    DATE_TRUNC('month', bp.date_execution)
ORDER BY
    mois DESC, bp.portefeuille, bp.utilisateur;

ALTER VIEW ma_moulinette.vw_batch_profiling_monthly OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.utilisateur TO db_user;

COMMENT ON VIEW ma_moulinette.vw_batch_profiling_monthly IS 'Vue mensuelle consolidée des performances des batchs (temps moyen, mémoire, nombre d’exécutions).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.portefeuille IS 'Nom du portefeuille traité.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.utilisateur IS 'Utilisateur ayant déclenché les traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.mois IS 'Mois et année du regroupement (format YYYY-MM).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.nb_executions IS 'Nombre total d’exécutions sur le mois.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.total_projets IS 'Nombre total de projets analysés durant le mois.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.temps_total_moyen_s IS 'Temps moyen total d’exécution sur le mois (secondes).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.memoire_peak_moyenne_mo IS 'Pic mémoire moyen mensuel (en Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.memoire_moyenne_mo IS 'Mémoire moyenne utilisée par projet durant le mois (en Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.memoire_peak_max_mo IS 'Pic mémoire le plus haut observé sur le mois.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.derniere_execution IS 'Dernière exécution du mois enregistrée.';

-- ===============================================
-- VIEW: ma_moulinette.vw_batch_profiling_global
-- ===============================================

DROP VIEW IF EXISTS ma_moulinette.vw_batch_profiling_global;
CREATE OR REPLACE VIEW ma_moulinette.vw_batch_profiling_global AS
SELECT
    bp.portefeuille,
    bp.utilisateur,
    COUNT(bp.id) AS nb_executions_total,
    SUM(bp.nb_projets) AS total_projets,
    ROUND(AVG(bp.temps_total)::numeric, 2) AS temps_total_moyen_s,
    ROUND(AVG(bp.temps_moyen)::numeric, 2) AS temps_moyen_projet_s,
    ROUND(AVG(bp.memoire_peak)::numeric, 2) AS memoire_peak_moyenne_mo,
    ROUND(AVG(bp.memoire_moyenne)::numeric, 2) AS memoire_moyenne_mo,
    ROUND(MAX(bp.memoire_peak)::numeric, 2) AS memoire_peak_max_mo,
    MIN(bp.date_execution) AS premiere_execution,
    MAX(bp.date_execution) AS derniere_execution
FROM ma_moulinette.batch_profiling bp
GROUP BY
    bp.portefeuille,
    bp.utilisateur
ORDER BY
    bp.portefeuille,
    bp.utilisateur;

ALTER VIEW ma_moulinette.vw_batch_profiling_global OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.utilisateur TO db_user;

COMMENT ON VIEW ma_moulinette.vw_batch_profiling_global IS 'Vue de synthèse globale des performances (moyennes, pics mémoire, historique complet).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.portefeuille IS 'Nom du portefeuille traité.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.utilisateur IS 'Utilisateur à l’origine des traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.nb_executions_total IS 'Nombre total d’exécutions enregistrées pour ce couple portefeuille/utilisateur.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.total_projets IS 'Nombre total de projets analysés sur l’historique complet.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.temps_total_moyen_s IS 'Temps moyen global d’exécution pour le portefeuille.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.memoire_peak_moyenne_mo IS 'Pic mémoire moyen global (en Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.memoire_moyenne_mo IS 'Mémoire moyenne utilisée par projet sur l’historique.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.memoire_peak_max_mo IS 'Pic mémoire maximum observé sur l’historique.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.premiere_execution IS 'Date de la première exécution enregistrée.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.derniere_execution IS 'Date de la dernière exécution enregistrée.';

-- ===============================================
-- VIEW: ma_moulinette.vw_batch_profiling_summary
-- ===============================================

DROP VIEW IF EXISTS ma_moulinette.vw_batch_profiling_summary;
CREATE OR REPLACE VIEW ma_moulinette.vw_batch_profiling_summary AS

-- 1. Niveau hebdomadaire
SELECT
    'weekly'::text AS granularite,
    portefeuille,
    utilisateur,
    TO_CHAR(semaine, 'IYYY-"S"IW') AS periode,  -- ex: 2025-S45
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

-- Niveau mensuel
SELECT
    'monthly'::text AS granularite,
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

-- Niveau global (historique complet)
SELECT
    'global'::text AS granularite,
    portefeuille,
    utilisateur,
    'ALL_TIME'::text AS periode,
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
GRANT ALL ON TABLE ma_moulinette.utilisateur TO db_user;

COMMENT ON VIEW ma_moulinette.vw_batch_profiling_summary IS 'Vue unifiée des statistiques de performance (hebdo, mensuel, global).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.granularite IS 'Niveau de regroupement : weekly | monthly | global.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.periode IS 'Identifiant de période (ex: 2025-S45, 2025-11, ALL_TIME).';

-- ===============================================
-- INDEXES: ma_moulinette.batch_profiling
-- ===============================================

-- Index principal sur la date pour accélérer les regroupements temporels
CREATE INDEX IF NOT EXISTS idx_batch_profiling_date_execution ON ma_moulinette.batch_profiling (date_execution DESC);

-- Index combiné portefeuille + utilisateur pour les vues filtrées
CREATE INDEX IF NOT EXISTS idx_batch_profiling_portefeuille_user ON ma_moulinette.batch_profiling (portefeuille, utilisateur);

-- Index sur portefeuille seul (utile pour agrégations globales)
CREATE INDEX IF NOT EXISTS idx_batch_profiling_portefeuille ON ma_moulinette.batch_profiling (portefeuille);

-- Index sur utilisateur seul (utile pour requêtes par utilisateur)
CREATE INDEX IF NOT EXISTS idx_batch_profiling_utilisateur ON ma_moulinette.batch_profiling (utilisateur);

-- Index sur la référence d’exécution (utile si on recherche un batch précis)
CREATE INDEX IF NOT EXISTS idx_batch_profiling_execution_ref ON ma_moulinette.batch_profiling (execution_reference);

-- TODO : Index partiel — uniquement sur les 3 derniers mois
-- ⚠️ utile si la base devient volumineuse (> 100k lignes)
--CREATE INDEX IF NOT EXISTS idx_batch_profiling_recent ON ma_moulinette.batch_profiling (date_execution) WHERE date_execution >= (NOW() - INTERVAL '3 months');

-- ===============================================
-- Vérification des droits
-- ===============================================
ALTER TABLE ma_moulinette.batch_profiling OWNER TO db_user;
GRANT SELECT, INSERT, UPDATE, DELETE ON ma_moulinette.batch_profiling TO db_user;

-- ===============================================
-- Commentaires
-- ===============================================
COMMENT ON INDEX ma_moulinette.idx_batch_profiling_date_execution IS 'Index sur la date d’exécution pour accélérer les regroupements temporels.';
COMMENT ON INDEX ma_moulinette.idx_batch_profiling_portefeuille_user IS 'Index composite sur portefeuille et utilisateur pour optimiser les vues agrégées.';
-- TODO : COMMENT ON INDEX ma_moulinette.idx_batch_profiling_recent IS 'Index partiel sur les 3 derniers mois pour optimiser les analyses récentes.';

--------------------------------------------------------------------
-----                                                        -------
-----                Historique des changements              -------
-----                                                        -------
--------------------------------------------------------------------
-- 02/11/2025 : Laurent HADJADJ - Ajout d'une vue sur la table batch_profiling pour les statistiques quotidiennes.
-- 02/11/2025 : Laurent HADJADJ - Ajout d'une vue sur la table batch_profiling pour les statistiques hedomadaire.
-- 02/11/2025 : Laurent HADJADJ - Ajout d'une vue sur la table batch_profiling pour les statistiques mensuelles.
-- 02/11/2025 : Laurent HADJADJ - Ajout d'une vue sur la table batch_profiling pour les statistiques globales.
-- 03/11/2025 : Externalisation des vues dans un script dédiée. Code-Clean.
