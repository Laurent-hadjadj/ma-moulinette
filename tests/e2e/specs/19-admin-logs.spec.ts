import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetE2EData } from '../helpers/db';
import { USERS } from '../helpers/users';
import { seedAdminLogFiles, cleanupAdminLogFiles } from '../helpers/logs';

/**
 * Spec 19 — Admin Logs (/admin/logs).
 *
 * Acteur : interne (ROLE_INTERNAL, actif nativement dans fixtures-e2e.sql —
 * pas de seed dédié au-delà d'un reset, même approche que le spec 01).
 *
 * Le contrôle d'accès négatif (403 sans ROLE_INTERNAL) est déjà couvert par
 * le spec 08 (page listée parmi les routes strictes `#[IsGranted]`) — ce
 * spec exerce uniquement le fonctionnement réel de la page : filtres
 * env/type, sélection de lignes, téléchargement ZIP.
 *
 * Fichiers de log propagées directement sur le filesystem (voir helpers/logs.ts
 * pour la justification détaillée) : il n'existe pas de flux applicatif
 * fiable pour produire ces données en e2e.
 */
test.describe('19 — Admin Logs', () => {
  test.beforeAll(() => {
    resetE2EData();
    seedAdminLogFiles();
  });

  test.afterAll(() => {
    cleanupAdminLogFiles();
  });

  test('interne consulte, filtre et télécharge une sélection de logs', async ({ page }) => {
    await login(page, USERS.interne);
    await page.goto('/admin/logs');
    await expect(page).toHaveURL(/\/admin\/logs$/);

    await test.step('1. Chargement initial (env par défaut = environnement courant "test")', async () => {
      await expect(page.locator('#logs-table')).toBeVisible();
      await expect(page.locator('#bouton-download-zip')).toBeVisible();

      // app-test.log (env=test) et deprecations-test.log (env=test) visibles :
      // on les vérifie d'abord pour forcer l'attente de la fin du chargement
      // AJAX avant de vérifier les absences ci-dessous (sans quoi une
      // assertion toHaveCount(0) passerait trivialement sur un tableau
      // encore vide).
      await expect(page.locator('td.log-name', { hasText: 'app-test.log' })).toBeVisible();
      await expect(page.locator('td.log-name', { hasText: 'deprecations-test.log' })).toBeVisible();

      // Fixtures dev (request-dev.log, messenger-dev.log) et prod (prod.log)
      // filtrées par l'environnement par défaut.
      await expect(page.locator('td.log-name', { hasText: 'request-dev.log' })).toHaveCount(0);
      await expect(page.locator('td.log-name', { hasText: 'messenger-dev.log' })).toHaveCount(0);
      await expect(page.locator('td.log-name', { hasText: 'prod.log' })).toHaveCount(0);
    });

    await test.step('2. Filtre environnement = Dev', async () => {
      await page.locator('#filter-env').selectOption('dev');
      await page.locator('#bouton-apply-filters').click();

      await expect(page.locator('td.log-name', { hasText: 'request-dev.log' })).toBeVisible();
      await expect(page.locator('td.log-name', { hasText: 'messenger-dev.log' })).toBeVisible();
      await expect(page.locator('td.log-name', { hasText: 'app-test.log' })).toHaveCount(0);
    });

    await test.step('3. Filtre type = Application (env remis à "courant")', async () => {
      await page.locator('#filter-env').selectOption('');
      await page.locator('#filter-application').check();
      await page.locator('#bouton-apply-filters').click();

      await expect(page.locator('td.log-name', { hasText: 'app-test.log' })).toBeVisible();
      // Même environnement (test) mais type différent (deprecation) : exclu
      // par le filtre de type, pas par l'environnement.
      await expect(page.locator('td.log-name', { hasText: 'deprecations-test.log' })).toHaveCount(0);

      await page.locator('#filter-application').uncheck();
      await page.locator('#bouton-apply-filters').click();
      await expect(page.locator('td.log-name', { hasText: 'deprecations-test.log' })).toBeVisible();
    });

    await test.step('4. Sélection : "tout cocher" / "tout décocher"', async () => {
      const checkboxes = page.locator('.log-select');
      await expect(checkboxes.first()).toBeVisible();
      const count = await checkboxes.count();

      await page.locator('#select-all').check();
      for (let i = 0; i < count; i++) {
        await expect(checkboxes.nth(i)).toBeChecked();
      }

      await page.locator('#select-all').uncheck();
      for (let i = 0; i < count; i++) {
        await expect(checkboxes.nth(i)).not.toBeChecked();
      }
    });

    await test.step('5. Clic sur une ligne (hors case) coche sa checkbox', async () => {
      const row = page.locator('#logs-table tbody tr', { hasText: 'app-test.log' });
      await row.locator('td.log-name').click();
      await expect(row.locator('.log-select')).toBeChecked();
      // On revient à l'état non sélectionné pour l'étape 6.
      await row.locator('td.log-name').click();
      await expect(row.locator('.log-select')).not.toBeChecked();
    });

    await test.step("6. Téléchargement sans sélection → message d'avertissement", async () => {
      await page.locator('#bouton-download-zip').click();
      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText(
        'Veuillez sélectionner au moins un fichier (Erreur 404).'
      );
    });

    await test.step('7. Téléchargement avec sélection → ZIP réel', async () => {
      const row = page.locator('#logs-table tbody tr', { hasText: 'app-test.log' });
      await row.locator('.log-select').check();

      // downloadSelection() appelle window.confirm() avant l'envoi. Sans
      // handler enregistré, Playwright rejette (dismiss) les dialogues par
      // défaut : confirm() renverrait alors false et la fonction
      // s'arrêterait avant le $.post, sans jamais déclencher le téléchargement.
      page.once('dialog', (dialog) => dialog.accept());

      const downloadPromise = page.waitForEvent('download');
      await page.locator('#bouton-download-zip').click();
      const download = await downloadPromise;

      // Nom fixé côté client (`a.download = 'logs_selectionnes.zip'`), qui
      // prime sur le nom horodaté renvoyé par le serveur dans
      // Content-Disposition (logs_YYYYMMDD_His.zip) — comportement normal du
      // navigateur sur une Blob URL générée en JS, pas un bug serveur.
      expect(download.suggestedFilename()).toBe('logs_selectionnes.zip');

      const path = await download.path();
      expect(path).not.toBeNull();
    });
  });
});
