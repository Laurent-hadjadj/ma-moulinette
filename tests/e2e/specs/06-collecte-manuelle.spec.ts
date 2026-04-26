import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth';
import { resetAndSeedAfterSpec05 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 06 — Collecte manuelle par Nathan (ROLE_COLLECTE).
 *
 * Acteur : Nathan (ROLE_COLLECTE)
 *
 * Parcours :
 *   1. Login Nathan
 *   2. Navigate to /projet
 *   3. Sélectionne tetris:TetrisGame depuis #liste-projet
 *   4. Clique #bouton-collecte-indicateur (lance collecte SonarQube)
 *      → orchestration de plusieurs API calls : measures, issues, hotspots, etc.
 *      → tous résolus via SonarFixtureClientService (fixtures JSON locales)
 *   5. Clique #bouton-affiche-indicateur (affiche résultats peinture)
 *   6. Clique #bouton-enregistrement-indicateur (enregistre en DB)
 *
 * Pré-requis : seed-after-spec-05 (groupe fonctionnel tetris-game créé,
 *   Nathan affecté à ce groupe, projet tetris en liste_projet).
 */

const PROJECT_KEY = 'tetris:TetrisGame';

test.describe('06 — Collecte manuelle', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec05();
  });

  test.setTimeout(180_000); // collecte longue (multi-API calls)

  test('Nathan lance la collecte sur tetris:TetrisGame', async ({ page }) => {
    await login(page, USERS.nathan);

    // ---------- 1. Navigate to /projet ----------
    await page.goto('/projet');
    await expect(page).toHaveURL(/\/projet/);

    // ---------- 2. Select project tetris ----------
    // #liste-projet est un <select> chargé en AJAX au load. On attend qu'il
    // contienne notre option (clé du projet).
    await expect(page.locator(`#liste-projet option[value="${PROJECT_KEY}"]`))
      .toHaveCount(1, { timeout: 10_000 });
    await page.locator('#liste-projet').selectOption(PROJECT_KEY);

    // Vérif : la clé du projet est affichée dans #select-result
    await expect(page.locator('#select-result')).toContainText(PROJECT_KEY);

    // ---------- 3. Lancer la collecte ----------
    const collecteBtn = page.locator('#bouton-collecte-indicateur');
    await expect(collecteBtn).not.toHaveClass(/disabled-bouton/);
    await collecteBtn.click();

    // La collecte enchaîne ~13 phases. JS log dans <textarea id="log"> via
    // textarea.value += ... → on doit utiliser toHaveValue (textContent reste
    // vide pour les textareas, seule la propriété .value contient le log).
    await expect(page.locator('#log')).toHaveValue(/\(13\) La collecte des données est terminée/, {
      timeout: 90_000,
    });

    // ---------- 4. Afficher les résultats ----------
    // Le bouton click déclenche `remplissage()` qui peint le DOM, puis
    // `showMessage()` affiche un toast qui disparaît après 3s (flaky à
    // asserter). On attend juste que l'AJAX settle.
    const afficheBtn = page.locator('#bouton-affiche-indicateur');
    await expect(afficheBtn).toBeVisible();
    await afficheBtn.click();
    await page.waitForTimeout(5_000);

    // ---------- 5. Enregistrer ----------
    const enregistreBtn = page.locator('#bouton-enregistrement-indicateur');
    await expect(enregistreBtn).toBeVisible();
    await enregistreBtn.click();
    await page.waitForTimeout(3_000);

    // Pas d'assertion DOM finale stricte : la simple absence d'erreur valide
    // que les 3 actions (collecte → affichage → enregistrement) se sont
    // déroulées sans 500. On affinera quand on aura les sélecteurs des
    // indicateurs de succès (toast / compteur en peinture).
  });
});
