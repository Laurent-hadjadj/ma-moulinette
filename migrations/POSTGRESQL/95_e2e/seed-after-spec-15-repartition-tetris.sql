/*
####################################################
##                                                ##
##  Seed E2E - repartition complete (spec 15)     ##
##  V1.0.0 - 28/07/2026                           ##
##                                                ##
####################################################*/

-- Seed dédie au spec 15 (page Repartition, bouton "Historique") : insère
-- directement en base 1 ligne repartition control='complet (100%)' pour
-- tetris:TetrisGame, avec des compteurs granulaires reels par module ×
-- catégorie × sévérité.
--
-- Pourquoi un seed direct plutôt qu'un vrai cycle collecte->analyse : la
-- fixture SonarQube partagée (issues/search.json, deja enrichie pour le
-- spec 13 avec la facette cleanCodeAttributeCategories) ne fournit pas la
-- facette 'severities' attendue par BatchCollecteRepartitionController --
-- le total calcule au chargement de la page est donc toujours 0 et aucun
-- bouton de collecte ne déclenche le moindre appel API (cf. commentaire du
-- spec 15). Ce seed teste uniquement le bouton "Historique" (lecture pure),
-- pas le cycle collecte/analyse en direct.
--
-- Usage : appelé APRèS seed-after-spec-05-affectations.sql
--   (voir resetAndSeedForRepartition() dans tests/e2e/helpers/db.ts).

BEGIN;

INSERT INTO ma_moulinette.repartition
    (maven_key, name, setup, control,
        frontend, frontend_bug_blocker, frontend_bug_critical, frontend_bug_major,
        frontend_vulnerability_blocker, frontend_vulnerability_critical, frontend_vulnerability_major,
        frontend_code_smell_blocker, frontend_code_smell_critical, frontend_code_smell_major,
        backend, backend_bug_blocker, backend_bug_critical, backend_bug_major,
        backend_vulnerability_blocker, backend_vulnerability_critical, backend_vulnerability_major,
        backend_code_smell_blocker, backend_code_smell_critical, backend_code_smell_major,
        autre, inconnu,
        mode_collecte, utilisateur_collecte)
VALUES
    ('tetris:TetrisGame', 'TetrisGame', 20260715080000, 'complet (100%)',
        40, 1, 3, 6,   0, 2, 4,   0, 5, 14,
        70, 0, 2, 5,   0, 1, 3,   0, 7, 20,
        15, 7,
        'COLLECTE', 'e2e-seed');

COMMIT;

-- Verification
SELECT 'repartition tetris' AS scope, COUNT(*) AS nb
FROM ma_moulinette.repartition
WHERE maven_key = 'tetris:TetrisGame' AND control = 'complet (100%)';
