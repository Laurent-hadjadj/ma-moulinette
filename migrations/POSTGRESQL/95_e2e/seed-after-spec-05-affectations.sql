/*
####################################################
##                                                ##
##  Seed E2E - etat apres spec 05                 ##
##  V1.0.0 - 26/04/2026                           ##
##                                                ##
####################################################*/

-- Seed minimal qui replique l'etat post-spec 05 :
--   - 1 groupe fonctionnel "tetris-game" cree
--   - chaque user affecte a son groupe_utilisateur cible
--   - chaque user a liste_groupe_fonctionnel = ["tetris-game"]
--     (necessaire pour que les users voient le projet tetris en /projet :
--      ApiProjetController fait `tag LIKE 'tetris-game%'`)
--
-- Usage : appele APRES seed-after-spec-04-projet-tetris.sql.

-- MODIF 2026-07-22 : \c ma_moulinette db_user retire — voir
-- reset-e2e-data.sql pour le detail (écrasait la cible -d/-U réelle du
-- script appelant, cause racine de l'incident du 2026-05-02).

BEGIN;

-- Creation du groupe fonctionnel tetris-game
INSERT INTO ma_moulinette.groupe_fonctionnel
    (groupe_fonctionnel, description, date_enregistrement)
VALUES
    ('tetris-game', 'Groupe fonctionnel E2E pour tetris:TetrisGame', CURRENT_TIMESTAMP);

-- Affectation des groupes utilisateur (replicat des actions UI de spec 05)
UPDATE ma_moulinette.utilisateur
SET groupe_utilisateur = 'admin',
    liste_groupe_fonctionnel = '["tetris-game"]'::json
WHERE courriel = 'interne@ma-moulinette.fr';

UPDATE ma_moulinette.utilisateur
SET groupe_utilisateur = 'consultation',
    liste_groupe_fonctionnel = '["tetris-game"]'::json
WHERE courriel = 'josh.liberman@ma-moulinette.fr';

UPDATE ma_moulinette.utilisateur
SET groupe_utilisateur = 'collecte',
    liste_groupe_fonctionnel = '["tetris-game"]'::json
WHERE courriel = 'nathan.jones@ma-moulinette.fr';

UPDATE ma_moulinette.utilisateur
SET groupe_utilisateur = 'gestionnaire metier',
    liste_groupe_fonctionnel = '["tetris-game"]'::json
WHERE courriel = 'sophie.martin@ma-moulinette.fr';

UPDATE ma_moulinette.utilisateur
SET groupe_utilisateur = 'gestionnaire applicatif',
    liste_groupe_fonctionnel = '["tetris-game"]'::json
WHERE courriel = 'aurelie.petit-coeur@ma-moulinette.fr';

COMMIT;

-- Verification
SELECT courriel, groupe_utilisateur, liste_groupe_fonctionnel
FROM ma_moulinette.utilisateur
WHERE courriel LIKE '%@ma-moulinette.fr'
ORDER BY courriel;
