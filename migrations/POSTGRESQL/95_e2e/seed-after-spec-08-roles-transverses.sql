/*
####################################################
##                                                ##
##  Seed E2E - roles transverses (spec 08)        ##
##  V1.0.0 - 26/07/2026                           ##
##                                                ##
####################################################*/

-- Seed dedie au spec 08 (controle d'acces par role), independant du
-- scenario d'onboarding 01-07. Aucun des 5 users e2e ne porte nativement
-- ROLE_SECURITY, ROLE_ACTIVITY, ROLE_BATCH ou ROLE_ACTUATOR (modules
-- transverses, pas lies au parcours narratif utilise par les autres specs).
--
-- On les cumule tous sur Nathan (deja ROLE_COLLECTE pour le spec 06, mais
-- spec 08 fait son propre reset+seed independant donc pas de conflit) pour
-- servir de "cas positif" unique ; les autres users (Josh, Sophie, Aurelie)
-- servent de "cas negatif" (n'ont aucun de ces 4 roles).
--
-- Usage : appele APRES seed-after-spec-03-users-actives.sql via
-- bin/e2e/seed-e2e.ps1 (voir helpers/db.ts::resetAndSeedAfterSpec08).

BEGIN;

UPDATE ma_moulinette.utilisateur
SET roles = '["ROLE_COLLECTE","ROLE_SECURITY","ROLE_ACTIVITY","ROLE_BATCH","ROLE_ACTUATOR"]'::json
WHERE courriel = 'nathan.jones@ma-moulinette.fr';

COMMIT;

-- Verification
SELECT courriel, roles FROM ma_moulinette.utilisateur WHERE courriel = 'nathan.jones@ma-moulinette.fr';
