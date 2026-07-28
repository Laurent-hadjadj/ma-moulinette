import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedForActivity } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 17 — Page Activité (tableau de bord SonarQube, ROLE_ACTIVITY).
 *
 * Acteur : Nathan, seul user e2e portant ROLE_ACTIVITY (cumulé par le seed
 *   spec 08 des rôles transverses — aucun user du parcours narratif ne le
 *   porte nativement). Le contrôle d'accès négatif (403 pour un user sans
 *   ce rôle) est déjà couvert par 08-controle-acces.spec.ts, pas reproduit
 *   ici : ce spec se concentre sur le workflow fonctionnel réel.
 *
 * Prérequis de données : depuis la migration de mai 2026, `activity` n'est
 *   alimentée que par le cron `app:activity:collecte` — jamais par l'UI ni
 *   par le flux de collecte manuelle. Le seed insère donc directement 3
 *   lignes (2 SUCCESS, 1 FAILED) pour tetris:TetrisGame sur l'année en
 *   cours, sans peupler `activity_historique` : la page démarre donc en
 *   état "pas encore de statistiques", et le bouton "Recalculer les
 *   statistiques" exerce la vraie agrégation (ApiActivityController::
 *   sauvegardeHistorique(), pas un simple mock de lecture).
 */
test.describe('17 — Activité', () => {
  test.beforeAll(() => {
    resetAndSeedForActivity();
  });

  test.setTimeout(60_000);

  test('Nathan recalcule les statistiques et trace les graphiques', async ({ page }) => {
    await login(page, USERS.nathan);
    await page.goto('/activity');
    await expect(page).toHaveURL(/\/activity$/);
    await expect(page.locator('#fil-ariane')).toContainText('Activité');

    await test.step('1. Pas encore de statistiques (activity_historique vide)', async () => {
      await expect(page.locator('.js-flash-box')).toContainText(
        "Aucune statistique n'a encore été générée"
      );
      // ActivityController::index() rend une ligne de repli ('---' partout)
      // tant qu'aucune agrégation n'a eu lieu — pas une table vide.
      await expect(page.locator('#js-tableau-stats tr')).toHaveCount(1);
      await expect(page.locator('#js-tableau-stats tr td').first()).toHaveText('---');
    });

    await test.step('2. Recalculer les statistiques — agrégation réelle activity -> activity_historique', async () => {
      await page.locator('#bouton-activity-refresh').click();

      const row = page.locator('#js-tableau-stats tr');
      await expect(row).toHaveCount(1);

      const cells = row.locator('td');
      const currentYear = new Date().getFullYear().toString();
      await expect(cells.nth(0)).toHaveText(currentYear); // Année
      await expect(cells.nth(2)).toHaveText('3'); // Analyse (total)
      await expect(cells.nth(4)).toHaveText('2'); // Succès
      await expect(cells.nth(5)).toHaveText('1'); // Échec
      await expect(cells.nth(6)).toHaveText('66.7 %'); // Taux de réussite = round(2/3*100, 1)
      await expect(cells.nth(7)).toHaveText('00:02:00'); // Temp Max = max(execution_time) = 120s
    });

    await test.step('3. Recalcul persiste après rechargement (lecture activity_historique)', async () => {
      await page.reload({ waitUntil: 'domcontentloaded' });
      await expect(page.locator('#js-tableau-stats tr')).toHaveCount(1);
      await expect(page.locator('#js-tableau-stats tr td').nth(2)).toHaveText('3');
    });

    await test.step('4. Les 3 boutons de tracé fonctionnent (pas d\'erreur remontée)', async () => {
      for (const buttonId of [
        'bouton-courbe-analyse',
        'bouton-courbe-projet',
        'bouton-courbe-analyse-projet',
      ]) {
        await page.locator(`#${buttonId}`).click();
        await expect(page.locator('#message-box')).toHaveClass(/hide/);
      }
    });
  });
});
