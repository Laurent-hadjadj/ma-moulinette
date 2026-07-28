/*
####################################################
##                                                ##
##  Seed E2E - activity tetris (spec 17)          ##
##  V1.0.0 - 28/07/2026                           ##
##                                                ##
####################################################*/

-- Seed dédié au spec 17 (page Activité) : insère directement en base 3 lignes
-- `activity` (2 SUCCESS, 1 FAILED) pour tetris:TetrisGame, sur l'année en
-- cours (via CURRENT_DATE plutôt qu'une date figée, pour rester valide
-- indéfiniment — la page agrège par EXTRACT(YEAR FROM started_at)).
--
-- Pourquoi un seed direct plutôt qu'une vraie collecte : depuis la migration
-- de mai 2026, la table `activity` n'est plus alimentée que par le cron
-- quotidien (`app:activity:collecte`), jamais depuis l'UI ni depuis le flux
-- de collecte manuelle testé par spec 06 — sans ce seed, `activity` reste
-- vide et le bouton "Recalculer les statistiques" renvoie 204 (rien à
-- agréger).
--
-- Valeurs choisies pour un calcul déterministe côté
-- ApiActivityController::sauvegardeHistorique() : 3 analyses (2 succès,
-- 1 échec) → success_rate = round(2/3*100, 1) = 66.7 ; execution_time max
-- = 120s → max_time affiché "00:02:00". Le "jour" (écart 1re analyse ->
-- aujourd'hui) et la moyenne qui en dépend restent, eux, non déterministes
-- (dépendent de la date réelle d'exécution du test) : non asserés par le spec.
--
-- Usage : appelé APRÈS seed-after-spec-08-roles-transverses.sql (Nathan porte
-- ROLE_ACTIVITY) — voir resetAndSeedForActivity() dans tests/e2e/helpers/db.ts.

BEGIN;

INSERT INTO ma_moulinette.activity
    (maven_key, project_name, analyse_id, status, submitter_login,
        submitted_at, started_at, executed_at, execution_time)
VALUES
    ('tetris:TetrisGame', 'TetrisGame', 'e2e-activity-1', 'SUCCESS', 'nathan.jones',
        date_trunc('year', CURRENT_DATE) + INTERVAL '1 day',
        date_trunc('year', CURRENT_DATE) + INTERVAL '1 day',
        date_trunc('year', CURRENT_DATE) + INTERVAL '1 day' + INTERVAL '2 minutes',
        120),
    ('tetris:TetrisGame', 'TetrisGame', 'e2e-activity-2', 'SUCCESS', 'nathan.jones',
        date_trunc('year', CURRENT_DATE) + INTERVAL '5 days',
        date_trunc('year', CURRENT_DATE) + INTERVAL '5 days',
        date_trunc('year', CURRENT_DATE) + INTERVAL '5 days' + INTERVAL '90 seconds',
        90),
    ('tetris:TetrisGame', 'TetrisGame', 'e2e-activity-3', 'FAILED', 'nathan.jones',
        date_trunc('year', CURRENT_DATE) + INTERVAL '9 days',
        date_trunc('year', CURRENT_DATE) + INTERVAL '9 days',
        date_trunc('year', CURRENT_DATE) + INTERVAL '9 days' + INTERVAL '30 seconds',
        30);

COMMIT;

-- Vérification
SELECT status, COUNT(*) AS nb
FROM ma_moulinette.activity
WHERE maven_key = 'tetris:TetrisGame'
GROUP BY status
ORDER BY status;
