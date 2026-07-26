import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { gotoCrudIndex, gotoCrudNew } from '../helpers/admin';
import { resetAndSeedAfterSpec04 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 05 — Affectation des groupes utilisateur + création groupe fonctionnel.
 *
 * Acteur : Aurélie (ROLE_GESTIONNAIRE)
 *
 * Parcours :
 *   1. Aurélie crée un groupe fonctionnel "tetris-test" en sélectionnant le tag
 *      "tetris-game" (loaded depuis liste_projet, importée en spec 04)
 *   2. Pour chaque user, Aurélie l'affecte à son groupe utilisateur cible :
 *      - interne   → admin
 *      - Josh      → consultation
 *      - Nathan    → collecte
 *      - Sophie    → gestionnaire metier
 *      - Aurélie   → gestionnaire applicatif (self-edit)
 *
 * Pré-requis : seed-after-spec-04 (tout l'état post-spec 04 + 1 projet tetris).
 *
 * Note : groupe utilisateur normalisé en minuscules par le contrôleur (cf. spec 02).
 */

const FUNCTIONAL_GROUP_NAME = 'tetris-test';
const FUNCTIONAL_GROUP_TAG  = 'tetris-game';

const USER_GROUP_ASSIGNMENTS = [
  { user: USERS.interne, groupe: 'admin' },
  { user: USERS.josh,    groupe: 'consultation' },
  { user: USERS.nathan,  groupe: 'collecte' },
  { user: USERS.sophie,  groupe: 'gestionnaire metier' },
  { user: USERS.aurelie, groupe: 'gestionnaire applicatif' },
];

test.describe('05 — Affectation des groupes', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec04();
  });

  test.setTimeout(120_000);

  test('Aurélie crée le groupe fonctionnel et affecte chaque user à son groupe', async ({ page }) => {
    await login(page, USERS.aurelie);

    // ---------- 1. Création du groupe fonctionnel ----------
    await gotoCrudNew(page, 'groupeFonctionnel');
    await expect(page).toHaveURL(/\/admin\/groupe-fonctionnel\/new/);

    // Tag : ChoiceField avec js-tags class. Sélection du tag tetris-game.
    // En EasyAdmin v4, ChoiceField simple = <select>. Selectionner via selectOption.
    await page.locator('select.js-tags').selectOption(FUNCTIONAL_GROUP_TAG);

    // Nom du groupe fonctionnel
    await page.locator('input.js-groupe').fill(FUNCTIONAL_GROUP_NAME);

    // Description
    await page.locator('input[name$="[description]"]').fill('Groupe fonctionnel E2E pour tetris');

    // Submit (bouton EasyAdmin "Créer", exact)
    await page.getByRole('button', { name: 'Créer', exact: true }).click();

    // Vérif : retour vers la liste des groupes fonctionnels
    await expect(page).toHaveURL(/\/admin\/groupe-fonctionnel(\/|$|\?)/);

    // ---------- 2. Affectation des users aux groupes utilisateur ----------
    for (const { user, groupe } of USER_GROUP_ASSIGNMENTS) {
      // Le save peut déconnecter Aurélie (self-edit ou édition d'internal),
      // dans ce cas on re-login avant l'itération suivante.
      if (/\/login/.test(page.url())) {
        await login(page, USERS.aurelie);
      }

      await gotoCrudIndex(page, 'utilisateur');

      // Trouver la ligne du user et naviguer vers son edit
      const row = page.locator('tr').filter({ hasText: user.email });
      const editHref = await row.locator('a[data-action-name="edit"]').first().getAttribute('href');
      expect(editHref, `lien edit manquant pour ${user.email}`).toBeTruthy();
      await page.goto(editHref!);
      await expect(page).toHaveURL(/\/admin\/utilisateur\/\d+\/edit/);

      // Sélectionner le groupe utilisateur dans le dropdown.
      // Champ : ChoiceField('groupeUtilisateur') → <select> avec les groupes.
      await page.locator('select[name$="[groupeUtilisateur]"]').selectOption(groupe);

      // Sauvegarder
      await page.getByRole('button', { name: /^(sauvegarder|enregistrer)/i }).first().click();

      // Attente du load suivant. Soit on revient sur /admin/utilisateur (cas
      // standard), soit on est redirigé sur /login si la session a été
      // invalidée par le save (self-edit Aurélie / edit internal).
      await page.waitForLoadState('networkidle').catch(() => {});
    }
  });
});
