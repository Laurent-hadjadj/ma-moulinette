/*
####################################################
##                                                ##
##  Seed E2E - historique tetris pour spec 07     ##
##  V1.0.0 - 27/07/2026                           ##
##                                                ##
####################################################*/

-- Seed dédié au spec 07 (page Suivi) : insère directement 2 lignes
-- d'historique pour tetris:TetrisGame, sans passer par une vraie collecte
-- SonarQube (le fixture project_analyses/search-page1.json n'a qu'une seule
-- entrée, ce qui ne permet pas de produire 2 versions distinctes via le flux
-- réel de collecte). Ce seed teste uniquement les actions de la page Suivi
-- (définir la version de référence, supprimer une version) indépendamment
-- du mécanisme de collecte, déjà couvert par le spec 06.
--
-- Usage : appelé APRèS seed-after-spec-05-affectations.sql
--   (voir resetAndSeedForSuiviHistorique() dans tests/e2e/helpers/db.ts).

BEGIN;

INSERT INTO ma_moulinette.historique
    (maven_key, version, date_version, analyse_key, project_name, initial, mode_collecte, utilisateur_collecte)
VALUES
    ('tetris:TetrisGame', '1.0.0-RELEASE', '2026-01-15T10:00:00+0100', 'e2e-analyse-key-v1', 'TetrisGame', true,  'COLLECTE', 'e2e-seed'),
    ('tetris:TetrisGame', '1.1.0-RELEASE', '2026-04-22T08:59:02+0200', 'e2e-analyse-key-v2', 'TetrisGame', false, 'COLLECTE', 'e2e-seed');

COMMIT;

-- Verification
SELECT 'historique tetris' AS scope, COUNT(*) AS nb
FROM ma_moulinette.historique
WHERE maven_key = 'tetris:TetrisGame';
