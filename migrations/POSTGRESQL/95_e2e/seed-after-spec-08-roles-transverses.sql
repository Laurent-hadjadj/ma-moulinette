/*
####################################################
##                                                ##
##  Seed E2E - roles transverses (spec 08)        ##
##  V1.0.0 - 26/07/2026                           ##
##                                                ##
####################################################*/

-- Seed dedie au spec 08 (controle d'acces par role), independant du
-- scenario d'onboarding 01-07. Aucun des 5 users e2e ne porte nativement
-- ROLE_SECURITY, ROLE_ACTIVITY, ROLE_BATCH, ROLE_ACTUATOR ou ROLE_REFERENTIAL
-- (modules transverses, pas lies au parcours narratif utilise par les autres
-- specs).
--
-- On les cumule tous sur Nathan (deja ROLE_COLLECTE pour le spec 06, mais
-- spec 08 fait son propre reset+seed independant donc pas de conflit) pour
-- servir de "cas positif" unique ; les autres users (Josh, Sophie, Aurelie)
-- servent de "cas negatif" (n'ont aucun de ces 5 roles).
--
-- MODIF 22/11/2026 : ajout de ROLE_REFERENTIAL (nouveau role dedie, ex-
-- ROLE_GESTIONNAIRE, qui protege desormais /api/secure/accueil/projet et
-- /api/secure/accueil/tags - cf. ApiAccueilController).
--
-- Usage : appele APRES seed-after-spec-03-users-actives.sql via
-- bin/e2e/seed-e2e.ps1 (voir helpers/db.ts::resetAndSeedAfterSpec08).

BEGIN;

UPDATE ma_moulinette.utilisateur
SET roles = '["ROLE_COLLECTE","ROLE_SECURITY","ROLE_ACTIVITY","ROLE_BATCH","ROLE_ACTUATOR","ROLE_REFERENTIAL"]'::json
WHERE courriel = 'nathan.jones@ma-moulinette.fr';

COMMIT;

-- Verification
SELECT courriel, roles FROM ma_moulinette.utilisateur WHERE courriel = 'nathan.jones@ma-moulinette.fr';
