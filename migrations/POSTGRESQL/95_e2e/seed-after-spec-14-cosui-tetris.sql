/*
####################################################
##                                                ##
##  Seed E2E - historique + repartition COSUI     ##
##  V1.0.0 - 28/07/2026                           ##
##                                                ##
####################################################*/

-- Seed dédie au spec 14 (page COSUI) : insère directement en base une ligne
-- d'historique "reference" (initial=true) + une ligne "courante", avec des
-- notes/compteurs reels (pas seulement les colonnes squelette du seed spec 07),
-- ainsi qu'une ligne repartition control='complet (100%)' pour tetris:TetrisGame.
--
-- Pourquoi un seed direct plutôt qu'une vraie collecte : la fixture SonarQube
-- (project_analyses) ne produit qu'une seule analyse (pas de ligne initial=true
-- distincte), et le flux spec 06 ne déclenche jamais l'action "Repartition par
-- module" (aucune ligne repartition avec control <> 'initial'). Sans ces deux
-- prérequis, la page COSUI reste en mode "valeurs par défaut" (pas d'erreur
-- bloquante mais rien d’intéressant à verifier).
--
-- Usage : appelé APRèS seed-after-spec-05-affectations.sql
--   (voir resetAndSeedForCosui() dans tests/e2e/helpers/db.ts).

BEGIN;

INSERT INTO ma_moulinette.historique
    (maven_key, version, date_version, analyse_key, project_name, initial,
        reliability_rating, security_rating, security_review_rating, sqale_rating,
        bug_blocker, bug_critical, bug_major,
        vulnerability_blocker, vulnerability_critical, vulnerability_major,
        code_smell_blocker, code_smell_critical, code_smell_major,
        coverage, sqale_debt_ratio, mode_collecte, utilisateur_collecte)
VALUES
    ('tetris:TetrisGame', '1.0.0-RELEASE', '2026-01-15T10:00:00+0100', 'e2e-cosui-key-v1', 'TetrisGame', true,
        'D', 'C', 'B', 'C',
        2, 5, 10,
        1, 3, 6,
        0, 8, 20,
        55.0, 8.5, 'COLLECTE', 'e2e-seed'),
    ('tetris:TetrisGame', '1.1.0-RELEASE', '2026-04-22T08:59:02+0200', 'e2e-cosui-key-v2', 'TetrisGame', false,
        'A', 'A', 'A', 'A',
        0, 1, 3,
        0, 0, 2,
        0, 2, 10,
        82.0, 3.2, 'COLLECTE', 'e2e-seed');

-- Colonnes granulaires lues par ProjetCosuiService::traitement() (module ×
-- prefix × sévérité, ex. frontendBugBlocker -> frontend_bug_blocker) pour
-- alimenter le tableau "Répartition des défauts" (colonnes Métier=backend,
-- Présentation=frontend, cf. ProjetCosuiService.php:146-153).
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
    ('tetris:TetrisGame', 'TetrisGame', 20260722120000, 'complet (100%)',
        40, 1, 2, 5,   0, 1, 3,   0, 4, 12,
        70, 0, 1, 4,   0, 2, 5,   0, 6, 18,
        15, 7,
        'COLLECTE', 'e2e-seed');

COMMIT;

-- Verification
SELECT 'historique tetris' AS scope, COUNT(*) AS nb
FROM ma_moulinette.historique
WHERE maven_key = 'tetris:TetrisGame'
UNION ALL
SELECT 'repartition tetris', COUNT(*)
FROM ma_moulinette.repartition
WHERE maven_key = 'tetris:TetrisGame';
