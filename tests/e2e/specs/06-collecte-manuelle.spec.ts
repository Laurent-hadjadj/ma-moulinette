import { test, expect } from '../helpers/fixtures';
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
 *      → vérifie le code HTTP 200 de chaque appel /api/secure/peinture/projet/*
 *   6. Clique #bouton-enregistrement-indicateur (enregistre en DB)
 *      → vérifie le code HTTP 200 de POST /api/secure/enregistrement
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

    // La collecte enchaîne ~14 phases. JS log dans <textarea id="log"> via
    // textarea.value += ... → on doit utiliser toHaveValue (textContent reste
    // vide pour les textareas, seule la propriété .value contient le log).
    // Le numéro de phase n'est volontairement pas figé dans le pattern : il a
    // déjà changé une fois (13 → 14 avec l'ajout de la collecte Actuator) et
    // ce n'est pas ce que ce test vérifie.
    await expect(page.locator('#log')).toHaveValue(/\(\d+\) La collecte des données est terminée/, {
      timeout: 90_000,
    });

    // ---------- 4. Afficher les résultats (peinture) ----------
    // Le clic déclenche `remplissage()` (peinture.js) qui enchaîne ~11 appels
    // GET/POST vers /api/secure/peinture/projet/* (version, mesures, anomalie,
    // hotspots, nosonar, todo, logger, actuator...), puis
    // `afficheHotspotDetails()` si nécessaire. On capture toutes les réponses
    // de ce préfixe et on vérifie leur code HTTP plutôt que de se contenter de
    // l'absence de crash visible.
    const peintureResponses: number[] = [];
    const onPeintureResponse = (response: import('@playwright/test').Response) => {
      if (response.url().includes('/api/secure/peinture/')) {
        peintureResponses.push(response.status());
      }
    };
    page.on('response', onPeintureResponse);

    const afficheBtn = page.locator('#bouton-affiche-indicateur');
    await expect(afficheBtn).toBeVisible();
    await afficheBtn.click();
    await page.waitForTimeout(5_000);
    page.off('response', onPeintureResponse);

    expect(peintureResponses.length, 'aucun appel /api/secure/peinture/* capturé').toBeGreaterThan(0);
    for (const status of peintureResponses) {
      expect(status, `un appel peinture a répondu ${status}`).toBe(200);
    }

    // ---------- 5. Enregistrer ----------
    // Un seul appel POST /api/secure/enregistrement — on attend explicitement
    // sa réponse plutôt qu'un timeout arbitraire, et on vérifie son code.
    const enregistreBtn = page.locator('#bouton-enregistrement-indicateur');
    await expect(enregistreBtn).toBeVisible();

    const [enregistrementResponse] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('/api/secure/enregistrement'), { timeout: 10_000 }),
      enregistreBtn.click(),
    ]);
    expect(enregistrementResponse.status()).toBe(200);
  });
});
