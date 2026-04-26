import { test, expect } from '@playwright/test';
import { login } from '../helpers/auth';
import { gotoCrudIndex } from '../helpers/admin';
import { resetAndSeedAfterSpec02 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 03 — Activation des rôles utilisateur.
 *
 * Acteur : interne (ROLE_INTERNAL)
 *
 * Pour chaque utilisateur E2E (Josh, Nathan, Sophie, Aurélie) :
 *   1. Ouvre la fiche en édition depuis la liste
 *   2. Coche `actif`
 *   3. Décoche `Aucun accès` (ROLE_NONE)
 *   4. Coche le ou les rôles cibles
 *   5. Sauvegarde
 *
 * Affectations cibles :
 *   - Josh    → ROLE_UTILISATEUR
 *   - Nathan  → ROLE_COLLECTE
 *   - Sophie  → ROLE_COLLECTE + ROLE_SUIVI
 *   - Aurélie → ROLE_GESTIONNAIRE
 *
 * Pré-requis : 5 groupes utilisateur en DB (replicat spec 02 via SQL seed
 *   pour permettre l'isolation lors du debug).
 */

/**
 * Sélecteur d'un checkbox de rôle dans le ChoiceField expanded EasyAdmin.
 * On cible par `value=` car le label "Utilisateur" est ambigu (matche aussi
 * le combobox du groupe utilisateur dans le formulaire entité "Utilisateur").
 */
function roleCheckbox(role: string): string {
  return `input[type="checkbox"][value="${role}"]`;
}

const ACTIVATIONS: { user: typeof USERS[keyof typeof USERS]; targetRoles: string[] }[] = [
  { user: USERS.josh,    targetRoles: ['ROLE_UTILISATEUR'] },
  { user: USERS.nathan,  targetRoles: ['ROLE_COLLECTE'] },
  { user: USERS.sophie,  targetRoles: ['ROLE_COLLECTE', 'ROLE_SUIVI'] },
  { user: USERS.aurelie, targetRoles: ['ROLE_GESTIONNAIRE'] },
];

test.describe('03 — Activation des rôles', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec02();
  });

  // Test plus long que la moyenne (4 users à éditer via UI ~6s chacun + reset DB).
  test.setTimeout(120_000);

  test('interne active les 4 users et leur attribue les rôles cibles', async ({ page }) => {
    await login(page, USERS.interne);

    for (const { user, targetRoles } of ACTIVATIONS) {
      // 1. Liste users → trouver la ligne du user → naviguer vers son edit.
      //    EasyAdmin v4 cache les actions dans un dropdown (`dropdown-item`),
      //    donc on extrait le href du lien edit et on goto directement.
      await gotoCrudIndex(page, 'utilisateur');
      const row = page.locator('tr').filter({ hasText: user.email });
      const editHref = await row.locator('a[data-action-name="edit"]').first().getAttribute('href');
      expect(editHref, `lien edit manquant pour ${user.email}`).toBeTruthy();
      await page.goto(editHref!);
      await expect(page).toHaveURL(/\/admin\/utilisateur\/\d+\/edit/);

      // 2. Activer
      await page.getByLabel('Actif').check();

      // 3. Décocher ROLE_NONE
      await page.locator(roleCheckbox('ROLE_NONE')).uncheck();

      // 4. Cocher les rôles cibles
      for (const role of targetRoles) {
        await page.locator(roleCheckbox(role)).check();
      }

      // 5. Sauvegarder (bouton primaire EasyAdmin "Sauvegarder les modifications"
      //    ou "Enregistrer les modifications" selon traduction)
      await page.getByRole('button', { name: /^(sauvegarder|enregistrer)/i }).first().click();

      // Retour liste après save
      await expect(page).toHaveURL(/\/admin\/utilisateur(\/|$|\?)/);
    }

    // Vérification finale : la page liste s'affiche et chaque email est présent.
    // EasyAdmin v4 wrappe les lignes dans des liens cliquables → on évite tr/row
    // strict matching, on se contente de vérifier que les emails apparaissent.
    await gotoCrudIndex(page, 'utilisateur');
    for (const { user } of ACTIVATIONS) {
      await expect(page.getByText(user.email).first()).toBeVisible();
    }
  });
});
