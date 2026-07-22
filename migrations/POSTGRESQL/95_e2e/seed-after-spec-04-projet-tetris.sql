/*
####################################################
##                                                ##
##  Seed E2E - etat apres spec 04                 ##
##  V1.0.0 - 26/04/2026                           ##
##                                                ##
####################################################*/

-- Seed minimal qui replique l'etat post-spec 04 :
--   - 5 groupes utilisateur (spec 02)
--   - 4 users actifs avec roles (spec 03)
--   - Aurelie reset_password=false (le flow reset a deja ete teste)
--   - 1 projet tetris:TetrisGame en liste_projet avec tag "tetris-game"
--     (replicat de l'update projets faite en spec 04)
--
-- Sert pour spec 05 : Aurelie cree un groupe fonctionnel en selectionnant
-- le tag "tetris-game" depuis la liste_projet.
--
-- Usage : appele APRES seed-after-spec-03-users-actives.sql.

-- MODIF 2026-07-22 : \c ma_moulinette db_user retire — voir
-- reset-e2e-data.sql pour le detail (écrasait la cible -d/-U réelle du
-- script appelant, cause racine de l'incident du 2026-05-02).

BEGIN;

-- Reset Aurelie reset_password=false (spec 04 done)
UPDATE ma_moulinette.utilisateur
SET reset_password = false
WHERE courriel = 'aurelie.petit-coeur@ma-moulinette.fr';

-- Insertion du projet tetris (replicat de l'update projets en spec 04)
INSERT INTO ma_moulinette.liste_projet
    (maven_key, name, tags, visibility, date_enregistrement)
VALUES
    ('tetris:TetrisGame', 'TetrisGame', '["tetris-game"]'::json, 'public', CURRENT_TIMESTAMP);

COMMIT;

-- Verification
SELECT 'liste_projet' AS scope, COUNT(*) AS nb FROM ma_moulinette.liste_projet
UNION ALL
SELECT 'aurelie reset_password=false',
    CASE WHEN reset_password = false THEN 1 ELSE 0 END
FROM ma_moulinette.utilisateur
WHERE courriel = 'aurelie.petit-coeur@ma-moulinette.fr';
