import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedForRepartition } from '../helpers/db';
import { buildProjetToken } from '../helpers/token';
import { USERS } from '../helpers/users';

/**
 * Spec 15 — Module Répartition (bouton "Historique").
 *
 * Acteur : Nathan (ROLE_COLLECTE, aucun rôle transverse — même périmètre
 *   tetris-game que spec 06/11/13/14). `/repartition` exige ROLE_COLLECTE
 *   (seul rôle métier vérifié, à la fois pour le bouton sur /projet et pour
 *   les 4 routes API). Token identique à Clean Code/COSUI (`buildProjetToken()`).
 *
 * Comme COSUI (spec 14), le cycle réel (collecte → analyse) n'est pas
 * jouable avec les fixtures actuelles : `BatchCollecteRepartitionController`
 * attend une facette `severities` que `issues/search.json` ne fournit pas
 * (enrichie pour spec 13 avec `cleanCodeAttributeCategories`, pas
 * `severities`) — le total lu au chargement de la page est donc toujours 0
 * et chaque bouton de collecte affiche juste "pas de données à collecter"
 * sans jamais appeler l'API. Ce spec teste donc uniquement le bouton
 * "Historique" (lecture pure d'une ligne déjà complète), via un seed direct
 * en base (`seed-after-spec-15-repartition-tetris.sql`,
 * `resetAndSeedForRepartition()`) — pas le cycle collecte/analyse en direct.
 *
 * Bug réel trouvé et corrigé le 2026-07-28 (signalé par l'utilisateur) :
 * le mode Historique réutilisait le calcul d'IdC (Indice de Confiance) de
 * l'analyse en direct, qui divise le total historisé par les compteurs
 * *live* du DOM (figés au chargement de la page, donc décorrélés du moment
 * de l'analyse historisée) — l'IdC affiché n'avait donc aucun sens. Corrigé
 * dans `assets/js/mon-application/repartition-module/index-repartition-module.js`
 * (`generateTableRow()`) : l'IdC n'est plus recalculé en mode Historique,
 * affiché "---" (déjà le comportement par défaut de `buildTabHistorique()`
 * pour un IdC à 0). Ce spec vérifie explicitement ce "---".
 */
const PROJECT_KEY = 'tetris:TetrisGame';

test.describe('15 — Répartition', () => {
  test.beforeAll(() => {
    resetAndSeedForRepartition();
  });

  test.setTimeout(60_000);

  test('Nathan consulte l\'historique de répartition pour tetris', async ({ page }) => {
    await login(page, USERS.nathan);

    const token = buildProjetToken(PROJECT_KEY);
    await page.goto(`/repartition?token=${token}`);
    await expect(page).toHaveURL(/\/repartition\?token=/);
    await expect(page.locator('#titre-repartition')).toContainText('TetrisGame');

    await page.locator('#bouton-historique').click();

    await expect(page.locator('#js-analyse-mode')).toHaveText('Historique');
    await expect(page.locator('#js-analyse-setup')).toHaveText('20260715080000');

    // Tableau de synthèse (totaux agrégés par module).
    await expect(page.locator('#tableau-0')).toBeVisible();
    const syntheseRow = page.locator('#mon-bo-tableau-0 tr');
    await expect(syntheseRow.locator('td').nth(0)).toHaveText('40'); // frontend (Présentation)
    await expect(syntheseRow.locator('td').nth(1)).toHaveText('70'); // backend (Métier)

    // Fiabilité (bug) — ligne CRITICAL : compteurs réels + IdC "---" (bug corrigé).
    await expect(page.locator('#tableau-1')).toBeVisible();
    const bugCriticalRow = page.locator('#mon-bo-tableau-1 tr', { hasText: 'CRITICAL' });
    await expect(bugCriticalRow.locator('td').nth(1)).toHaveText('3'); // frontend_bug_critical
    await expect(bugCriticalRow.locator('td').nth(2)).toHaveText('2'); // backend_bug_critical
    await expect(bugCriticalRow.locator('td').last()).toHaveText('---');

    // Sécurité (vulnerability) — ligne CRITICAL : IdC "---" également.
    await expect(page.locator('#tableau-2')).toBeVisible();
    const vulnCriticalRow = page.locator('#mon-bo-tableau-2 tr', { hasText: 'CRITICAL' });
    await expect(vulnCriticalRow.locator('td').nth(1)).toHaveText('2'); // frontend_vulnerability_critical
    await expect(vulnCriticalRow.locator('td').last()).toHaveText('---');

    // Maintenabilité (code_smell) — ligne CRITICAL : IdC "---" également.
    await expect(page.locator('#tableau-3')).toBeVisible();
    const codeSmellCriticalRow = page.locator('#mon-bo-tableau-3 tr', { hasText: 'CRITICAL' });
    await expect(codeSmellCriticalRow.locator('td').nth(1)).toHaveText('5'); // frontend_code_smell_critical
    await expect(codeSmellCriticalRow.locator('td').last()).toHaveText('---');
  });
});
