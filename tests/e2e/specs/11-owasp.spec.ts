import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedAfterSpec05 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 11 — Module OWASP (affichage après collecte).
 *
 * Acteur : Nathan (ROLE_COLLECTE).
 *
 * Il n'existe pas de déclenchement OWASP isolé côté UI : la collecte OWASP
 * fait partie de l'orchestration unique de collecte générale (mêmes phases
 * que spec 06, `CollecteController` phases "Collecte des menaces OWASP" /
 * "...potentielles"). Le bouton `#bouton-analyse-owasp` sur /projet ne fait
 * que naviguer vers `/owasp?token=...` (token = ROT13+base64 de
 * `salt|maven_key`, généré côté template) — il ne relance rien. Ce spec
 * rejoue donc le flux complet de spec 06 avant de vérifier /owasp.
 *
 * `OwaspController` utilise le même trait `ProjetPerimetreGuard` que
 * /projet et /suivi (mêmes messages "Erreur 404"/"Erreur 406" si périmètre
 * invalide) — non re-testé ici, déjà couvert par les messages génériques du
 * trait.
 *
 * Sélecteurs volontairement DOM plutôt que visuels : le canvas Chart.js
 * (`#owasp-bar-chart`) n'est pas inspectable en Playwright (contenu = pixels,
 * pas de nœuds DOM) — on vérifie sa présence/visibilité, pas son contenu, et
 * on cible plutôt les données à côté (résumé chiffré, tableau détaillé).
 *
 * Pré-requis : seed post-spec-05 (groupe fonctionnel + projet tetris dans le
 * périmètre de Nathan), comme spec 06/07.
 */
const PROJECT_KEY = 'tetris:TetrisGame';

test.describe('11 — Module OWASP', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec05();
  });

  test.setTimeout(180_000);

  test('Nathan lance la collecte puis consulte le dashboard OWASP', async ({ page }) => {
    await login(page, USERS.nathan);

    await test.step('1. Collecte générale (identique spec 06)', async () => {
      await page.goto('/projet');
      await expect(page).toHaveURL(/\/projet/);

      await expect(page.locator(`#liste-projet option[value="${PROJECT_KEY}"]`))
        .toHaveCount(1, { timeout: 10_000 });
      await page.locator('#liste-projet').selectOption(PROJECT_KEY);
      await expect(page.locator('#select-result')).toContainText(PROJECT_KEY);

      const collecteBtn = page.locator('#bouton-collecte-indicateur');
      await expect(collecteBtn).not.toHaveClass(/disabled-bouton/);
      await collecteBtn.click();

      await expect(page.locator('#log')).toHaveValue(/\(\d+\) La collecte des données est terminée/, {
        timeout: 90_000,
      });
    });

    await test.step('2. Navigation vers /owasp via le bouton dédié', async () => {
      const owaspBtn = page.locator('#bouton-analyse-owasp');
      await expect(owaspBtn).toBeVisible();

      await Promise.all([
        page.waitForURL(/\/owasp\?token=/, { timeout: 15_000 }),
        owaspBtn.click(),
      ]);
    });

    await test.step('3. Vérification du dashboard OWASP', async () => {
      // Résumé chiffré (toujours présent, même à 0).
      await expect(page.locator('#nombre-faille-owasp')).toBeVisible();

      // Tableau de synthèse par catégorie OWASP (a1..a10).
      await expect(page.locator('#a1')).toBeVisible();
      await expect(page.locator('#a10')).toBeVisible();

      // Tableau détaillé des vulnérabilités, rempli en JS après collecte.
      await expect(page.locator('#tbody')).toBeAttached();

      // Chart.js : présence uniquement, contenu non inspectable (canvas).
      await expect(page.locator('#owasp-bar-chart-container canvas#owasp-bar-chart')).toBeVisible();
    });
  });
});
