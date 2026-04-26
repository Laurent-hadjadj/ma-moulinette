/*
####################################################
##                                                ##
##  Seed E2E - etat apres spec 03                 ##
##  V1.0.0 - 26/04/2026                           ##
##                                                ##
####################################################*/

-- Seed minimal qui replique l'etat post-spec 03 :
--   - 5 groupes utilisateur en place (depuis spec 02)
--   - 4 users E2E actives avec leur role cible
--   - Aurelie a reset_password=true (cas nominal du flow reset password
--     a la 1ere connexion, teste une seule fois en spec 04)
--
-- Usage : appele APRES reset-e2e-data.sql + seed-after-spec-02-groupes.sql
-- via bin/e2e/seed-e2e.ps1.

\c ma_moulinette db_user

BEGIN;

-- Josh -> ROLE_UTILISATEUR active
UPDATE ma_moulinette.utilisateur
SET actif = true, roles = '["ROLE_UTILISATEUR"]'::json
WHERE courriel = 'josh.liberman@ma-moulinette.fr';

-- Nathan -> ROLE_COLLECTE active
UPDATE ma_moulinette.utilisateur
SET actif = true, roles = '["ROLE_COLLECTE"]'::json
WHERE courriel = 'nathan.jones@ma-moulinette.fr';

-- Sophie -> ROLE_COLLECTE + ROLE_SUIVI active
UPDATE ma_moulinette.utilisateur
SET actif = true, roles = '["ROLE_COLLECTE","ROLE_SUIVI"]'::json
WHERE courriel = 'sophie.martin@ma-moulinette.fr';

-- Aurelie -> ROLE_GESTIONNAIRE active + reset_password=true
-- (on declenche le flow nominal "1ere connexion -> reset password")
UPDATE ma_moulinette.utilisateur
SET actif = true,
    roles = '["ROLE_GESTIONNAIRE"]'::json,
    reset_password = true
WHERE courriel = 'aurelie.petit-coeur@ma-moulinette.fr';

COMMIT;

-- Verification rapide
SELECT courriel, actif, roles, reset_password
FROM ma_moulinette.utilisateur
WHERE courriel LIKE '%@ma-moulinette.fr'
ORDER BY courriel;
