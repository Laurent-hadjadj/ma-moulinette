/*
####################################################
##                                                ##
##  Seed E2E - preference tetris (spec 18)        ##
##  V1.0.0 - 28/07/2026                           ##
##                                                ##
####################################################*/

-- Seed dédié au spec 18 (page Préférences) : peuple directement la colonne
-- JSON `utilisateur.preference` de Nathan pour tetris:TetrisGame (déjà
-- présent en base depuis seed-after-spec-04-projet-tetris.sql), sur les 3
-- catégories (suivi_projet, favori_projet, favori_version) — sans ce seed,
-- les 5 users e2e démarrent tous avec des listes vides (voir
-- migrations/POSTGRESQL/90_fixtures/fixtures-e2e.sql), rendant les 3
-- modales triviales à vérifier (aucune ligne, rien à supprimer).
--
-- Forme de favori_version confirmée par UtilisateurRepository::
-- updateUtilisateurFavoriVersion() : une liste d'objets à une seule clé
-- (mavenKey -> liste de versions), pas une map indexée directement par
-- mavenKey — d'où l'usage d'un index numérique dans l'API de suppression
-- (apiPreferenceVersionDelete).
--
-- Usage : appelé APRÈS seed-after-spec-05-affectations.sql (voir
-- resetAndSeedForPreferences() dans tests/e2e/helpers/db.ts).

BEGIN;

UPDATE ma_moulinette.utilisateur
SET preference = '{
    "statut": {"suivi_projet": true, "favori_projet": true, "favori_version": true},
    "suivi_projet": ["tetris:TetrisGame"],
    "favori_projet": ["tetris:TetrisGame"],
    "favori_version": [{"tetris:TetrisGame": ["1.0.0-RELEASE", "1.1.0-RELEASE"]}]
}'::json
WHERE courriel = 'nathan.jones@ma-moulinette.fr';

COMMIT;

-- Vérification
SELECT courriel, preference FROM ma_moulinette.utilisateur WHERE courriel = 'nathan.jones@ma-moulinette.fr';
