/*
####################################################
##                                                ##
##  Reset rapide de l'etat E2E (Phase K)          ##
##  V1.0.0 - 26/04/2026                           ##
##                                                ##
####################################################*/

-- Reset rapide pour les tests E2E Playwright.
--
-- Equivalent du reset entre tests d'integration Symfony :
--   1. Vide les tables mutees par les scenarios E2E
--   2. Re-charge les 5 utilisateurs E2E dans leur etat initial
--
-- Conserve :
--   - Les referentiels (owasp_top10, ma_moulinette versions)
--   - L'admin de fixtures.sql (admin@ma-moulinette.fr)
--   - Les groupes par defaut (Aucun, En attente)
--
-- Wipe :
--   - 5 utilisateurs E2E (puis re-INSERT via fixtures-e2e.sql)
--   - groupes utilisateur custom (cree par scenarios)
--   - groupes fonctionnels
--   - portefeuilles
--   - toutes les tables de donnees projet (collecte/peinture)
--   - tables d'activite et batch
--
-- Usage :
--   bin/e2e/reset-e2e-data.ps1
--   ou directement : psql -U db_user -d ma_moulinette -f reset-e2e-data.sql

\c ma_moulinette db_user

BEGIN;

-- ============================================================================
-- 1. Donnees de collecte/peinture (TRUNCATE rapide, CASCADE pour FK)
-- ============================================================================
TRUNCATE TABLE
    ma_moulinette.information_projet,
    ma_moulinette.historique,
    ma_moulinette.mesures,
    ma_moulinette.anomalie,
    ma_moulinette.anomalie_details,
    ma_moulinette.hotspots,
    ma_moulinette.hotspot_details,
    ma_moulinette.hotspot_owasp,
    ma_moulinette.no_sonar,
    ma_moulinette.todo,
    ma_moulinette.logger,
    ma_moulinette.repartition,
    ma_moulinette.repartition_temp,
    ma_moulinette.profiles,
    ma_moulinette.profiles_historique,
    ma_moulinette.liste_projet,
    ma_moulinette.batch_profiling,
    ma_moulinette.batch_execution,
    ma_moulinette.batch_execution_journal,
    ma_moulinette.batch_traitement,
    ma_moulinette.activity,
    ma_moulinette.activity_batch_report,
    ma_moulinette.activity_historique,
    ma_moulinette.actuator,
    ma_moulinette.actuator_info,
    ma_moulinette.user_agent_event,
    ma_moulinette.user_agent_analysis,
    ma_moulinette.user_role_log,
    ma_moulinette.portefeuille_historique
RESTART IDENTITY CASCADE;

-- ============================================================================
-- 2. Metadonnees mutees par les scenarios E2E
-- ============================================================================
DELETE FROM ma_moulinette.groupe_fonctionnel;
DELETE FROM ma_moulinette.portefeuille;
DELETE FROM ma_moulinette.groupe_utilisateur
WHERE groupe_utilisateur NOT IN ('Aucun', 'En attente');

-- ============================================================================
-- 3. Utilisateurs E2E (DELETE complet pour reset des roles/actif/password)
--    NB : on garde admin@ma-moulinette.fr (load par fixtures.sql)
-- ============================================================================
DELETE FROM ma_moulinette.utilisateur
WHERE courriel IN (
    'interne@ma-moulinette.fr',
    'josh.liberman@ma-moulinette.fr',
    'nathan.jones@ma-moulinette.fr',
    'sophie.martin@ma-moulinette.fr',
    'aurelie.petit-coeur@ma-moulinette.fr'
);

COMMIT;

-- ============================================================================
-- 4. Re-charge les 5 utilisateurs E2E dans leur etat initial (idempotent)
-- ============================================================================
\ir ../90_fixtures/fixtures-e2e.sql

-- Verification rapide
SELECT 'utilisateurs E2E' AS scope, COUNT(*) AS nb
FROM ma_moulinette.utilisateur
WHERE courriel LIKE '%@ma-moulinette.fr'
UNION ALL
SELECT 'groupes utilisateur (hors defauts)', COUNT(*)
FROM ma_moulinette.groupe_utilisateur
WHERE groupe_utilisateur NOT IN ('Aucun', 'En attente')
UNION ALL
SELECT 'groupes fonctionnels', COUNT(*) FROM ma_moulinette.groupe_fonctionnel
UNION ALL
SELECT 'portefeuilles', COUNT(*) FROM ma_moulinette.portefeuille;
