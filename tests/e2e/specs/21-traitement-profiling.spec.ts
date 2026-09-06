import { test, expect } from '../helpers/fixtures';
import type { Page } from '@playwright/test';
import { login, logout } from '../helpers/auth';
import { gotoCrudNew } from '../helpers/admin';
import { resetAndSeedForCrudTransverse } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 21 — Traitement (file de collecte portefeuille) & Profiling.
 *
 * Acteur principal : Nathan (ROLE_COLLECTE natif + ROLE_BATCH cumulé via
 * resetAndSeedForCrudTransverse(), groupe fonctionnel "tetris-game" déjà
 * existant avec tetris:TetrisGame).
 *
 * Contexte : Système séparé, portefeuille-large.
 *  `BatchManuelController::traitementManuel()`
 * (POST /api/secure/traitement/start) boucle sur TOUS les projets d'un
 * Portefeuille et appelle en interne le même `CollecteController::collecte()`
 * — synchrone (pas de vrai worker asynchrone malgré `pendingWorker.js`, qui
 * ne sert qu'à mettre en file une 2e demande si un traitement tourne déjà).
 * C'est cette exécution qui produit les lignes `batch_profiling` que le
 * dashboard `/traitement/profiling` consulte ensuite — jamais testé avant ce
 * spec (seul son accès 403/flash souple l'était, au spec 08).
 *
 * Pas de seed direct pour `batch_profiling`/`batch_traitement` : contrairement
 * à COSUI/Répartition, le flux réel (créer un Portefeuille + un Batch coché
 * "Activé" via EasyAdmin, puis cliquer "Lancer" sur /traitement/suivi) est
 * praticable et donne un test bout-en-bout plus fidèle.
 *
 * Problème de données identifié : `Batch.activated`
 * vaut `false` par défaut (cf. `Entity/Batch.php`) et le spec 09 (CRUD
 * Batch/Portefeuille) ne coche jamais "Activé" — son Batch n'apparaît donc
 * jamais sur `/traitement/suivi` (filtré par `WHERE activated = true`). Cette
 * spec coche explicitement "Activé" pour obtenir une ligne visible et
 * cliquable.
 *
 * 🐛 Bug identifié et corrigé dans cette spec : `workInProgress()`
 * (assets/js/mon-application/batch/index-batch.js) construisait son options
 * `$.ajax` avec la propriété raccourcie `contentType,` — qui référence une
 * variable `contentType` inexistante dans le scope du module (l'import
 * s'appelle `content_type`, avec underscore, comme utilisé correctement à
 * tous les autres appels `$.ajax` du même fichier). `ReferenceError`
 * synchrone à chaque clic sur le bouton "I am human" (déclenchement manuel),
 * capturé silencieusement par le try/catch englobant de
 * `lancerTraitementSiPossible()` (message "Erreur de lancement traitement"
 * affiché, aucune requête réseau jamais envoyée). Ce bouton n'avait donc
 * jamais fonctionné, pour personne, avant ce correctif — masqué jusqu'ici
 * car seul l'accès à la page (403/flash) était testé (spec 08), jamais le
 * déclenchement réel. Corrigé en `contentType: content_type,`.
 *
 * 🐛 Deuxième bug identifié et corrigé, une fois le premier levé :
 * `BatchManuelController::traitementManuel()` créait et persistait DEUX
 * `BatchExecution` identiques (bloc de code dupliqué, même `$execution_id`,
 * deux `updateBatchTraitement()`) avant même de démarrer la boucle de
 * collecte. La première ligne restait orpheline (jamais rattachée au
 * journal des projets), rendant `selectBatchExecutionLastTraitementId()`
 * (`ORDER BY date_enregistrement DESC LIMIT 1`) instable en cas d'égalité de
 * timestamp — la modale Information tirait alors la mauvaise ligne, dont le
 * select2 "Consulter un journal" restait vide ("Aucun résultat trouvé").
 * Corrigé en supprimant le premier bloc dupliqué ; couvert par
 * `BatchManuelControllerTest::testTraitementManuelPersistsExactlyOneBatchExecutionAndUpdatesTwice()`.
 */
async function waitForJqueryClickBinding(page: Page, selector: string): Promise<void> {
  await page.waitForFunction((sel) => {
    const w = window as unknown as { $?: any };
    const el = document.querySelector(sel);
    if (!w.$ || !el) return false;
    const events = w.$._data(el, 'events');
    return !!events?.click?.length;
  }, selector);
}

test.describe('21 — Traitement & Profiling', () => {
  test.beforeAll(() => {
    resetAndSeedForCrudTransverse();
  });

  // Un vrai déclenchement de collecte portefeuille (même orchestration que
  // spec 06, ~13 phases) + création EasyAdmin + dashboard Profiling.
  test.setTimeout(180_000);

  test("Sophie (sans ROLE_BATCH) voit le flash d'accès refusé sur /traitement/profiling", async ({ page }) => {
    await login(page, USERS.sophie);
    await page.goto('/traitement/profiling');
    await expect(page.locator('.js-flash-box .callout-message')).toContainText(
      "rôle 'BATCH'"
    );
    await logout(page);
  });

  test('Nathan lance un traitement de portefeuille puis consulte le dashboard Profiling', async ({ page }) => {
    await login(page, USERS.nathan);

    const PORTEFEUILLE_NAME = 'tetris-traitement-e2e';
    const BATCH_TITRE = 'tetris-traitement - Lot E2E';

    await test.step('1. Création du portefeuille', async () => {
      await gotoCrudNew(page, 'portefeuille');
      await page.locator('select[name$="[groupeFonctionnel]"]').selectOption('tetris-game');
      await page.locator('select[name$="[liste][]"]').selectOption('tetris:TetrisGame');
      await page.locator('input[name$="[portefeuille]"]').fill(PORTEFEUILLE_NAME);
      await page.getByRole('button', { name: 'Créer', exact: true }).click();
      await expect(page).toHaveURL(/\/admin\/portefeuille(\/|$|\?)/);
    });

    await test.step('2. Création du batch — Activé coché, Automatique décoché (manuel)', async () => {
      await gotoCrudNew(page, 'batch');
      await page.getByLabel('Activé').check();
      await page.locator('input[name$="[titre]"]').fill(BATCH_TITRE);
      await page.locator('select[name$="[portefeuille]"]').selectOption('tetris-game');
      await page.locator('input[name$="[description]"]').fill('Batch créé par le spec e2e 21.');
      await page.getByRole('button', { name: 'Créer', exact: true }).click();
      await expect(page).toHaveURL(/\/admin\/batch(\/|$|\?)/);
    });

    // <tr id="1"> : un id numérique pur n'est pas un sélecteur CSS #id valide
    // (doit commencer par une lettre) — on cible via un sélecteur d'attribut.
    const row = page.locator('[id="1"]');
    const humanButton = page.locator('#i-am-human-1');

    await test.step('3. La ligne apparaît sur /traitement/suivi, mode Manuel', async () => {
      await page.goto('/traitement/suivi');
      await expect(row).toBeVisible();
      await expect(page.locator('#portefeuille-1')).toContainText('tetris-game');
      // Présent uniquement en mode MANUEL (traitement.mode_collecte !== "TRAITEMENT AUTOMATIQUE").
      await expect(humanButton).toBeVisible();
    });

    await test.step('4. Déclenche le traitement — vraie collecte portefeuille synchrone', async () => {
      await waitForJqueryClickBinding(page, '.i-am-human-svg');

      const startResponse = page.waitForResponse(
        (r) => r.url().includes('/api/secure/traitement/start'),
        { timeout: 90_000 }
      );
      await humanButton.click();
      const response = await startResponse;
      expect(response.status()).toBe(200);

      await expect(page.locator('#result-1')).toContainText('Succès');
      await expect(page.locator('#message-box')).toContainText(
        `La collecte pour les projets de tetris-game est terminée`
      );
    });

    await test.step("5. Modale Information — détails du traitement", async () => {
      const infoIcon = page.locator('#outil-1 .js-outil-info');
      await waitForJqueryClickBinding(page, '.js-outil-info');
      await infoIcon.click();

      const modal = page.locator('#modal-traitement-information');
      await expect(modal).toBeVisible();
      await expect(modal.locator('.js-nom-traitement')).toHaveText(BATCH_TITRE.toUpperCase());
      await expect(modal.locator('.js-portefeuille')).toHaveText('tetris-game');
      await expect(modal.locator('.js-nombre-projet')).toHaveText('1');
      await expect(modal.locator('.js-mode-collecte')).toHaveText('Manuel');
      await expect(modal.locator('.js-statut')).toContainText('Succès');
      await expect(modal.locator('.js-activated')).toContainText('Oui');
    });

    await test.step("6. Sélection du projet dans le journal — ouvre la modale Journal", async () => {
      // Select2 : le <select> natif est masqué, l'interaction réelle passe
      // par la boîte cliquable générée (id auto select2-<id-select>-container)
      // puis par l'option affichée dans la liste déroulante — un simple
      // selectOption() sur le <select> caché ne déclenche pas l'événement
      // custom 'select2:select' auquel le JS de la page est abonné.
      await page.locator('#select2-select-journal-container').click();
      const option = page.locator('.select2-results__option', { hasText: 'TetrisGame' });
      // Sous charge (serveur de dev mono-thread, fin de suite complète), le
      // rendu de la liste déroulante peut être en retard sur l'ouverture —
      // on attend explicitement l'option avant de cliquer plutôt qu'un clic
      // immédiat qui risquerait de manquer une liste pas encore peuplée.
      await expect(option).toBeVisible({ timeout: 15_000 });
      await option.click();

      const journalModal = page.locator('#modal-journal');
      await expect(journalModal).toBeVisible();
      await expect(journalModal.locator('.js-journal-nom')).toContainText('TetrisGame');
      await expect(journalModal.locator('.js-journal-content')).not.toContainText('Aucun journal chargé');

      await page.locator('#bouton-fermer-journal').click();
      await expect(journalModal).toBeHidden();
      await page.locator('#bouton-fermer-information').click();
      await expect(page.locator('#modal-traitement-information')).toBeHidden();
    });

    await test.step('7. Dashboard Profiling — consulte les données produites par le traitement', async () => {
      await page.goto('/traitement/profiling');
      await expect(page).toHaveURL(/\/traitement\/profiling$/);

      await expect(page.locator('#summaryTable tbody tr')).toHaveCount(1);
      await expect(page.locator('#tableExecutions tbody')).toContainText('tetris-game');

      for (const id of ['granularite', 'periode', 'utilisateur', 'portefeuille', 'exec', 'execution']) {
        const cards = page.locator(`#indicateur-${id} .card`);
        await expect(cards.first()).toBeVisible();
      }

      const canvases = [
        'chart-kpi-time-donut', 'chart-kpi-memory-donut', 'chart-kpi-memory-bar',
        'chartTime', 'chartMemory',
        'chartWeeklyTime', 'chartWeeklyMemory',
        'chartMonthlyTime', 'chartMonthlyMemory',
        'chartUsersTime', 'chartUsersMemory',
      ];
      for (const id of canvases) {
        await expect(page.locator(`#${id}`)).toBeAttached();
      }
    });
  });
});
