import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedForSuiviHistorique } from '../helpers/db';
import { refreshAdminStats } from '../helpers/stats';
import { USERS } from '../helpers/users';

/**
 * Spec 16 — Module Statistiques (5 pages de consultation).
 *
 * Acteur : Nathan (ROLE_COLLECTE, groupe tetris-game — même périmètre que
 *   spec 06/11/13/14/15). Aucune de ces 5 pages n'exige de rôle spécifique
 *   (seul ROLE_UTILISATEUR implicite via le pare-firewall global) : Nathan
 *   y accède donc normalement. Seul le bouton "Analyse UserAgent"/"Relancer
 *   l'analyse" (route runBatchAnalysis) exige ROLE_INTERNAL — absent du DOM
 *   pour Nathan (pas juste caché en CSS, `{% if is_granted(...) %}` côté
 *   template), vérifié en négatif sur /statistiques et /statistiques/utilisateur.
 *
 * Prérequis de données :
 *   - /statistiques/dashboard lit `var/admin-stats.json` (gitignoré, absent
 *     en CI) puis `migrations/admin-stats.json` (absent aussi) — repli sur
 *     des valeurs hardcodées en l'absence des deux fichiers. On exerce ici le
 *     chemin "vraies données" en générant `var/admin-stats.json` via
 *     `refreshAdminStats()` (invoque `app:admin:refresh-stats`, cloc +
 *     phpunit --list-tests) avant la visite de la page — sans figer
 *     d'assertion sur des chiffres précis (cloc varie avec le code), mais en
 *     vérifiant que le bandeau "données figées" a bien disparu.
 *   - /statistiques/ma-moulinette ne fait que des COUNT/SUM directs, sans
 *     jointure : fonctionne même sur une base peu peuplée.
 *   - /statistiques/projet lit `historique` (HistoriqueRepository::
 *     selectAllProjetsDerniereSynthese(), pas de jointure) : réutilise le
 *     seed déjà existant du spec 07 (`resetAndSeedForSuiviHistorique()`,
 *     2 lignes historique pour tetris:TetrisGame) plutôt qu'une vraie
 *     collecte (~180s) — les colonnes non peuplées par ce seed minimal
 *     s'affichent en "–", ce qui suffit à vérifier que la ligne existe.
 */
const PROJECT_KEY = 'tetris:TetrisGame';

test.describe('16 — Statistiques', () => {
  test.beforeAll(() => {
    resetAndSeedForSuiviHistorique();
    refreshAdminStats();
  });

  test.setTimeout(90_000);

  test('Nathan consulte les 5 pages de statistiques', async ({ page }) => {
    await login(page, USERS.nathan);

    await test.step('1. Page index — 4 cartes, bouton batch absent (ROLE_INTERNAL)', async () => {
      await page.goto('/statistiques');
      await expect(page).toHaveURL(/\/statistiques$/);

      await expect(page.locator('#stats-dashboard-title')).toBeVisible();
      await expect(page.locator('#stats-consommation-title')).toBeVisible();
      await expect(page.locator('#stats-projet-title')).toBeVisible();
      await expect(page.locator('#stats-user-title')).toBeVisible();

      await expect(page.locator('#bouton-lance-batch-analysis')).toHaveCount(0);
    });

    await test.step('2. Dashboard technique (via la carte) — vraies données générées', async () => {
      await page.locator('a.stats-card[aria-labelledby="stats-dashboard-title"]').click();
      await expect(page).toHaveURL(/\/statistiques\/dashboard/);
      await expect(page.locator('#fil-ariane')).toContainText('Statistiques');

      // refreshAdminStats() a généré var/admin-stats.json : le bandeau
      // "données figées" doit avoir disparu au profit du bandeau "Données
      // générées le ...", sans figer d'assertion sur les chiffres cloc.
      await expect(page.getByText('Statistiques statiques (données figées)')).toHaveCount(0);
      await expect(page.getByText('Données générées le')).toBeVisible();
      await expect(page.locator('table.table-code tbody tr')).toHaveCount(7);
    });

    await test.step('3. Sonar Report (ma-moulinette)', async () => {
      await page.goto('/statistiques/ma-moulinette');
      await expect(page.locator('#admin-stats-data')).toBeAttached();
      await expect(page.locator('#chart-anomalies')).toBeVisible();
      await expect(page.locator('#chart-projets')).toBeVisible();
    });

    await test.step('4. Statistiques par projet — ligne tetris présente', async () => {
      await page.goto('/statistiques/projet');
      await expect(page.locator('#table-projets')).toBeVisible();
      const row = page.locator('#table-projets tbody tr', { hasText: 'TetrisGame' });
      await expect(row).toHaveCount(1);
    });

    await test.step('5. Statistiques utilisateur — bouton batch absent (ROLE_INTERNAL)', async () => {
      await page.goto('/statistiques/utilisateur');
      // Canvas masqués côté JS tant qu'aucune activité utilisateur n'a été
      // trackée (data-* vide, "[]") — état attendu sur ce seed minimal, pas
      // une vérification de contenu : présence seulement, pas visibilité.
      await expect(page.locator('#chart-avg-session-duration')).toBeAttached();
      await expect(page.locator('#chart-nb-session-unique')).toBeAttached();
      await expect(page.locator('#period-form')).toBeVisible();
      await expect(page.locator('#bouton-lance-batch-analysis')).toHaveCount(0);
    });
  });
});
