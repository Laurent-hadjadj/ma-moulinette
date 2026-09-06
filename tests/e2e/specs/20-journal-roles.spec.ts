import { test, expect } from '../helpers/fixtures';
import type { Page } from '@playwright/test';
import { login } from '../helpers/auth';
import { gotoCrudIndex } from '../helpers/admin';
import { resetAndSeedAfterSpec05 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 20 — Journal des rôles (/admin/journal-roles).
 *
 * Acteur : interne (ROLE_INTERNAL). Le contrôle d'accès négatif (403 sans
 * ROLE_INTERNAL) est déjà couvert par le spec 08 — ce spec exerce le
 * fonctionnement réel de la page.
 *
 * `user_role_log` n'est alimentée que par un vrai passage dans
 * `UtilisateurCrudController::updateEntity()` (via `UserRoleLoggerService`),
 * uniquement si les rôles OU le statut actif changent réellement — jamais
 * par les seeds SQL directs (`seed-after-spec-0X-*.sql` ne fait que des
 * UPDATE bruts sur `utilisateur`, sans jamais passer par ce contrôleur).
 * Contrairement aux specs Admin Logs (19), COSUI (14) ou Répartition (15),
 * il n'y a donc pas besoin de seed direct ici : ce spec produit ses propres
 * données en effectuant deux vraies éditions via l'UI EasyAdmin (même idiome
 * que le spec 03), avant de consulter/filtrer/exporter/supprimer le journal
 * qu'elles ont réellement généré.
 */
function roleCheckbox(role: string): string {
  return `input[type="checkbox"][value="${role}"]`;
}

async function editUser(page: Page, email: string): Promise<void> {
  await gotoCrudIndex(page, 'utilisateur');
  const row = page.locator('tr').filter({ hasText: email });
  const editHref = await row.locator('a[data-action-name="edit"]').first().getAttribute('href');
  expect(editHref, `lien edit manquant pour ${email}`).toBeTruthy();
  await page.goto(editHref!);
  await expect(page).toHaveURL(/\/admin\/utilisateur\/\d+\/edit/);
}

async function saveUser(page: Page): Promise<void> {
  await page.getByRole('button', { name: /^(sauvegarder|enregistrer)/i }).first().click();
  await expect(page).toHaveURL(/\/admin\/utilisateur(\/|$|\?)/);
}

function isoDate(date: Date): string {
  return date.toISOString().slice(0, 10);
}

test.describe('20 — Journal des rôles', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec05();
  });

  // 2 éditions EasyAdmin réelles + plusieurs allers-retours de filtrage/export
  // (même ordre de grandeur que le spec 03, ~6s par édition).
  test.setTimeout(120_000);

  test('interne consulte, filtre, exporte et purge le journal des rôles', async ({ page }) => {
    await login(page, USERS.interne);

    await test.step("1. Génère deux transitions réelles via l'UI EasyAdmin", async () => {
      // Josh : changement de rôle pur (reste actif) — exerce l'affichage
      // "Rôles avant"/"Rôles après".
      await editUser(page, USERS.josh.email);
      await page.locator(roleCheckbox('ROLE_UTILISATEUR')).uncheck();
      await page.locator(roleCheckbox('ROLE_COLLECTE')).check();
      await saveUser(page);

      // Nathan : désactivation pure (rôles inchangés) — exerce l'affichage
      // "Actif avant → après".
      await editUser(page, USERS.nathan.email);
      await page.getByLabel('Actif').uncheck();
      await saveUser(page);
    });

    await page.goto('/admin/journal-roles');
    await expect(page).toHaveURL(/\/admin\/journal-roles$/);

    const joshRow = page.locator('#role-log-table tbody tr', { hasText: USERS.josh.email });
    const nathanRow = page.locator('#role-log-table tbody tr', { hasText: USERS.nathan.email });

    await test.step('2. Les deux lignes générées sont visibles avec le bon contenu', async () => {
      await expect(joshRow).toBeVisible();
      await expect(nathanRow).toBeVisible();

      await expect(joshRow).toContainText('ROLE_UTILISATEUR');
      await expect(joshRow).toContainText('ROLE_COLLECTE');
      await expect(joshRow).toContainText(USERS.interne.email); // éditeur

      // Nathan : actif avant=Oui (span "on") → après=Non (span "off").
      await expect(nathanRow.locator('.role-log-active-on')).toHaveCount(1);
      await expect(nathanRow.locator('.role-log-active-off')).toHaveCount(1);
      // Josh reste actif des deux côtés : aucun span "off" dans sa ligne.
      await expect(joshRow.locator('.role-log-active-off')).toHaveCount(0);
    });

    await test.step('3. Filtre courriel = cible (Nathan) → une seule ligne', async () => {
      await page.locator('#filter-courriel').fill(USERS.nathan.email);
      await page.locator('#bouton-apply-filters').click();

      await expect(nathanRow).toBeVisible();
      await expect(joshRow).toHaveCount(0);

      await page.locator('#filter-courriel').fill('');
    });

    await test.step("4. Filtre courriel = éditeur (interne) → les deux lignes (colonne OR)", async () => {
      await page.locator('#filter-courriel').fill(USERS.interne.email);
      await page.locator('#bouton-apply-filters').click();

      await expect(joshRow).toBeVisible();
      await expect(nathanRow).toBeVisible();

      await page.locator('#filter-courriel').fill('');
      await page.locator('#bouton-apply-filters').click();
    });

    await test.step('5. Filtre date excluant tout (Depuis le = demain) → aucune ligne', async () => {
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      await page.locator('#filter-start').fill(isoDate(tomorrow));
      await page.locator('#bouton-apply-filters').click();

      await expect(page.locator('#role-log-table tbody')).toContainText('Aucune ligne trouvée');

      await page.locator('#filter-start').fill('');
      await page.locator('#bouton-apply-filters').click();
      await expect(joshRow).toBeVisible();
      await expect(nathanRow).toBeVisible();
    });

    await test.step('6. Sélection : "tout cocher" / "tout décocher"', async () => {
      const checkboxes = page.locator('.role-log-select');
      await expect(checkboxes).toHaveCount(2);

      await page.locator('#select-all').check();
      await expect(checkboxes.nth(0)).toBeChecked();
      await expect(checkboxes.nth(1)).toBeChecked();

      await page.locator('#select-all').uncheck();
      await expect(checkboxes.nth(0)).not.toBeChecked();
      await expect(checkboxes.nth(1)).not.toBeChecked();
    });

    await test.step('7. Clic sur une ligne (hors case) coche sa checkbox', async () => {
      await joshRow.locator('td.role-log-date').click();
      await expect(joshRow.locator('.role-log-select')).toBeChecked();
      await joshRow.locator('td.role-log-date').click();
      await expect(joshRow.locator('.role-log-select')).not.toBeChecked();
    });

    await test.step("8. Archiver sans sélection → message d'avertissement", async () => {
      await page.locator('#bouton-archiver').click();
      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText(
        'Veuillez sélectionner au moins une ligne (Erreur 404).'
      );
    });

    await test.step('9. Archiver la ligne de Josh → CSV réel', async () => {
      await joshRow.locator('.role-log-select').check();

      const downloadPromise = page.waitForEvent('download');
      await page.locator('#bouton-archiver').click();
      const download = await downloadPromise;

      // Nom fixé côté client (a.download = 'journal_roles.csv'), qui prime
      // sur le nom horodaté renvoyé par le serveur en Content-Disposition —
      // même comportement navigateur que le spec 19 (Admin Logs).
      expect(download.suggestedFilename()).toBe('journal_roles.csv');
      expect(await download.path()).not.toBeNull();

      await joshRow.locator('.role-log-select').uncheck();
    });

    await test.step('10. Rapport PDF de la ligne de Nathan → PDF réel', async () => {
      await nathanRow.locator('.role-log-select').check();

      const downloadPromise = page.waitForEvent('download');
      await page.locator('#bouton-rapport-pdf').click();
      const download = await downloadPromise;

      expect(download.suggestedFilename()).toBe('journal_roles.pdf');
      expect(await download.path()).not.toBeNull();
    });

    await test.step('11. Supprimer la ligne de Nathan (déjà sélectionnée) → confirm() + CSRF', async () => {
      // deleteSelection() appelle window.confirm() avant l'envoi. Sans handler
      // enregistré, Playwright rejette (dismiss) les dialogues par défaut :
      // confirm() renverrait false et la suppression n'aurait jamais lieu —
      // même piège que le téléchargement du spec 19.
      page.once('dialog', (dialog) => dialog.accept());

      await page.locator('#bouton-supprimer').click();

      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText('1 ligne(s) supprimée(s).');

      // loadJournal() est rappelé après succès : la ligne de Nathan disparaît,
      // celle de Josh reste (persistance réelle, pas juste un masquage DOM).
      await expect(nathanRow).toHaveCount(0);
      await expect(joshRow).toBeVisible();
    });
  });
});
