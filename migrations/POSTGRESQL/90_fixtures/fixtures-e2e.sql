/*
####################################################
##                                                ##
##       Fixtures E2E Ma-Moulinette               ##
##       V2.0.0 - 26/04/2026                      ##
##                                                ##
####################################################*/

-- Fixtures dédiées aux tests End-to-End (Playwright, Phase K).
--
-- Modèle "onboarding séquentiel" :
--   1 user ROLE_INTERNAL actif (bootstrap admin E2E)
--   4 users ROLE_NONE disabled (actives + assignes en cours de scenario)
--
-- Le user internal est le SEUL qui peut se connecter au demarrage.
-- Il active les 4 autres et leur assigne leur role cible :
--   - Josh    -> ROLE_UTILISATEUR
--   - Nathan  -> ROLE_COLLECTE
--   - Sophie  -> ROLE_COLLECTE + ROLE_SUIVI
--   - Aurelie -> ROLE_GESTIONNAIRE
--
-- Aucun groupe utilisateur ni groupe fonctionnel attribue au depart :
-- groupe='Aucun' (défaut), liste_groupe_fonctionnel=[]. Les groupes
-- (ADMIN, CONSULTATION, COLLECTE, GESTIONNAIRE METIER, GESTIONNAIRE
-- APPLICATIF) sont crées par l'internal en cours de scenario via l'UI.
--
-- CONVENTION : password = courriel (bcrypt cost 13)
-- Généré via bin/e2e/generate-e2e-hashes.php
--
-- USAGE :
--   psql -U postgres -d ma_moulinette -v ON_ERROR_STOP=1 \
--        -f migrations/POSTGRESQL/90_fixtures/fixtures-e2e.sql
--
-- Idempotent : peut être relance sans erreur.

-- MODIF 2026-07-22 : \c ma_moulinette db_user remplacé par \c - db_user
-- (conserve la base courante, ne change que le rôle) — le nom de base en dur
-- écrasait silencieusement la cible -d réelle de l'appelant quand ce fichier
-- est tiré depuis un script pointant sur une AUTRE base (ex. reset-e2e-data.ps1
-- sur ma_moulinette_test).
\c - db_user

BEGIN;

-- ============================================================================
-- 1. INTERNE — ROLE_INTERNAL (actif, bootstrap E2E admin)
-- ============================================================================
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom,
    date_enregistrement, actif, avatar, liste_groupe_fonctionnel,
    groupe_utilisateur, groupe_id)
VALUES (
    '{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false},"suivi_projet":[],"favori_projet":[],"favori_version":[]}'::json,
    false,
    'interne@ma-moulinette.fr',
    '["ROLE_INTERNAL"]'::json,
    '$2y$13$JlicpFQ1i.8dvDw/km3eUOSFaT/P5YYdhYr3Ino3xqYTJ4CkhRlsa',
    'Interne', 'E2E',
    '1980-01-01 00:00:00', true, 'chiffre/02.png',
    '["@AUCUN"]'::json,
    'Aucun', '11111111111111111111111111'
)
ON CONFLICT (courriel) DO NOTHING;

-- ============================================================================
-- 2. JOSH LIBERMAN — ROLE_NONE (disabled, futur ROLE_UTILISATEUR)
-- ============================================================================
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom,
    date_enregistrement, actif, avatar, liste_groupe_fonctionnel,
    groupe_utilisateur, groupe_id)
VALUES (
    '{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false},"suivi_projet":[],"favori_projet":[],"favori_version":[]}'::json,
    false,
    'josh.liberman@ma-moulinette.fr',
    '["ROLE_NONE"]'::json,
    '$2y$13$GEOYGiuyGRAKufl7XddBHuYTrc9xZexVAh.aipq/BXw0XQKlHdl/G',
    'Josh', 'LIBERMAN',
    '1980-01-01 00:00:00', false, 'garcon-1/10.png',
    '["@AUCUN"]'::json,
    'Aucun', '11111111111111111111111111'
)
ON CONFLICT (courriel) DO NOTHING;

-- ============================================================================
-- 3. NATHAN JONES — ROLE_NONE (disabled, futur ROLE_COLLECTE)
-- ============================================================================
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom,
    date_enregistrement, actif, avatar, liste_groupe_fonctionnel,
    groupe_utilisateur, groupe_id)
VALUES (
    '{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false},"suivi_projet":[],"favori_projet":[],"favori_version":[]}'::json,
    false,
    'nathan.jones@ma-moulinette.fr',
    '["ROLE_NONE"]'::json,
    '$2y$13$gseCc/kAykkE1YasmtH4EO5AM7oP7MGhl16MTLcDqJClM00tzxp2a',
    'Nathan', 'JONES',
    '1980-01-01 00:00:00', false, 'garcon-1/05.png',
    '["@AUCUN"]'::json,
    'Aucun', '11111111111111111111111111'
)
ON CONFLICT (courriel) DO NOTHING;

-- ============================================================================
-- 4. SOPHIE MARTIN — ROLE_NONE (disabled, futur ROLE_COLLECTE + ROLE_SUIVI)
-- ============================================================================
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom,
    date_enregistrement, actif, avatar, liste_groupe_fonctionnel,
    groupe_utilisateur, groupe_id)
VALUES (
    '{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false},"suivi_projet":[],"favori_projet":[],"favori_version":[]}'::json,
    false,
    'sophie.martin@ma-moulinette.fr',
    '["ROLE_NONE"]'::json,
    '$2y$13$i0shoO8BOWdPvhbfoQUPAewWbFZjclp5oVf8bjwERqy9v5/rRtpR.',
    'Sophie', 'MARTIN',
    '1980-01-01 00:00:00', false, 'fille-1/03.png',
    '["@AUCUN"]'::json,
    'Aucun', '11111111111111111111111111'
)
ON CONFLICT (courriel) DO NOTHING;

-- ============================================================================
-- 5. AURELIE PETIT-COEUR — ROLE_NONE (disabled, futur ROLE_GESTIONNAIRE)
--    NB : reset_password=true sera positionne par l'internal au moment de
--    l'activation, pour forcer le change au premier login (test du flow
--    reset password en step 4 du scenario E2E).
-- ============================================================================
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom,
    date_enregistrement, actif, avatar, liste_groupe_fonctionnel,
    groupe_utilisateur, groupe_id)
VALUES (
    '{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false},"suivi_projet":[],"favori_projet":[],"favori_version":[]}'::json,
    false,
    'aurelie.petit-coeur@ma-moulinette.fr',
    '["ROLE_NONE"]'::json,
    '$2y$13$6qjRl6RDCIxFmaPGnUZ5AO5CRNp36IjCaDZoGLdaJ6bdyq8od5PQa',
    'Aurélie', 'PETIT COEUR',
    '1980-01-01 00:00:00', false, 'fille-1/05.png',
    '["@AUCUN"]'::json,
    'Aucun', '11111111111111111111111111'
)
ON CONFLICT (courriel) DO NOTHING;

COMMIT;

-- Verification rapide
SELECT courriel, roles, actif
FROM ma_moulinette.utilisateur
WHERE courriel IN (
    'interne@ma-moulinette.fr',
    'josh.liberman@ma-moulinette.fr',
    'nathan.jones@ma-moulinette.fr',
    'sophie.martin@ma-moulinette.fr',
    'aurelie.petit-coeur@ma-moulinette.fr'
)
ORDER BY courriel;
