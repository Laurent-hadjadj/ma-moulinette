import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { gotoCrudIndex, gotoCrudNew } from '../helpers/admin';
import { resetAndSeedForCrudTransverse } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 09 — CRUD Portefeuille et Batch (EasyAdmin, ROLE_BATCH).
 *
 * Acteur : Nathan (ROLE_COLLECTE natif + ROLE_BATCH cumulé via
 * resetAndSeedForCrudTransverse(), cf. helpers/db.ts).
 *
 * Ordre imposé par une dépendance de données réelle : le ChoiceField
 * "portefeuille" du formulaire Batch (BatchCrudController.php:181) est
 * peuplé depuis `SELECT groupe_fonctionnel FROM ma_moulinette.portefeuille`
 * — sans Portefeuille existant, seul le choix placeholder "Aucun" est
 * disponible. On crée donc un Portefeuille avant un Batch qui le référence.
 *
 * Pré-requis : seed post-spec-05 (groupe fonctionnel "tetris-test" existant,
 * nécessaire pour peupler le ChoiceField groupeFonctionnel du formulaire
 * Portefeuille) + ROLE_BATCH sur Nathan.
 */
test.describe('09 — CRUD Portefeuille et Batch', () => {
  test.beforeAll(() => {
    resetAndSeedForCrudTransverse();
  });

  test.setTimeout(60_000);

  test('Nathan crée un portefeuille puis un batch qui le référence', async ({ page }) => {
    await login(page, USERS.nathan);

    const PORTEFEUILLE_NAME = 'tetris-test-quotidien';

    await test.step('1. Création du portefeuille', async () => {
      await gotoCrudNew(page, 'portefeuille');
      await expect(page).toHaveURL(/\/admin\/portefeuille\/new/);

      // Valeur réelle stockée en base = le tag ("tetris-game"), pas le nom
      // d'affichage du groupe fonctionnel ("tetris-test" créé en spec 05).
      await page.locator('select[name$="[groupeFonctionnel]"]').selectOption('tetris-game');
      // "liste" est un <select multiple> enrichi TomSelect (caché) : selectOption()
      // sur le natif marche (cf. gotchas EasyAdmin de test-e2e.md).
      await page.locator('select[name$="[liste][]"]').selectOption('tetris:TetrisGame');
      await page.locator('input[name$="[portefeuille]"]').fill(PORTEFEUILLE_NAME);

      await page.getByRole('button', { name: 'Créer', exact: true }).click();
      await expect(page).toHaveURL(/\/admin\/portefeuille(\/|$|\?)/);
      // La liste affiche le nom en majuscules (CSS text-transform) : match
      // insensible à la casse plutôt que de dépendre du rendu visuel.
      await expect(page.locator('body')).toContainText(new RegExp(PORTEFEUILLE_NAME, 'i'));
    });

    await test.step('2. Création du batch référençant ce portefeuille', async () => {
      await gotoCrudNew(page, 'batch');
      await expect(page).toHaveURL(/\/admin\/batch\/new/);

      await page.locator('input[name$="[titre]"]').fill('tetris-test - Lot E2E');
      // Idem : "portefeuille" référence en réalité le groupe_fonctionnel du
      // Portefeuille créé à l'étape précédente ("tetris-game"), pas son nom.
      await page.locator('select[name$="[portefeuille]"]').selectOption('tetris-game');
      await page.locator('input[name$="[description]"]').fill('Batch créé par le spec e2e 09.');

      await page.getByRole('button', { name: 'Créer', exact: true }).click();
      await expect(page).toHaveURL(/\/admin\/batch(\/|$|\?)/);
      await expect(page.locator('body')).toContainText(/tetris-test - Lot E2E/i);
    });
  });
});
