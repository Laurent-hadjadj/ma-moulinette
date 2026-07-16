import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth';
import { resetAndSeedAfterSpec05 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 07 — Page Suivi (Sophie ROLE_COLLECTE+ROLE_SUIVI).
 *
 * Acteur : Sophie (ROLE_COLLECTE + ROLE_SUIVI)
 *
 * Parcours :
 *   1. Login Sophie
 *   2. Navigate to /projet
 *   3. Sélectionne tetris:TetrisGame depuis #liste-projet
 *   4. Clique #bouton-tableau-de-bord → navigation vers /suivi/set?token=...
 *      (jeton ROT13+base64, depuis 2026-07-16) qui redirige sur /suivi
 *   5. Vérifie qu'on est bien sur la page /suivi
 *
 * Note : le scénario complet de la page /suivi (suppression historique,
 *   définition version par défaut) sera précisé ultérieurement par le user.
 *   Pour l'instant on valide juste la nav + accès.
 *
 * Pré-requis : seed-after-spec-05 (groupe fonctionnel + Sophie a accès au
 *   projet tetris via liste_groupe_fonctionnel = ["tetris-game"]).
 */

const PROJECT_KEY = 'tetris:TetrisGame';

test.describe('07 — Page Suivi', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec05();
  });

  test.setTimeout(60_000);

  test('Sophie accède à la page de suivi pour tetris', async ({ page }) => {
    await login(page, USERS.sophie);

    // ---------- 1. Navigate to /projet ----------
    await page.goto('/projet');
    await expect(page).toHaveURL(/\/projet/);

    // ---------- 2. Select project tetris ----------
    await expect(page.locator(`#liste-projet option[value="${PROJECT_KEY}"]`))
      .toHaveCount(1, { timeout: 10_000 });
    await page.locator('#liste-projet').selectOption(PROJECT_KEY);
    await expect(page.locator('#select-result')).toContainText(PROJECT_KEY);

    // ---------- 3. Cliquer sur Suivi (tableau de bord) ----------
    const suiviBtn = page.locator('#bouton-tableau-de-bord');
    // Le bouton est désactivé tant qu'aucun projet n'est sélectionné. Après
    // sélection, le JS retire la classe disabled-bouton.
    await expect(suiviBtn).not.toHaveClass(/disabled-bouton/);
    await suiviBtn.click();

    // ---------- 4. Vérifier la nav vers /suivi ----------
    // Le clic redirige vers /suivi/set?token=... (jeton ROT13+base64) qui redirige vers /suivi.
    await expect(page).toHaveURL(/\/suivi/, { timeout: 15_000 });
  });
});
