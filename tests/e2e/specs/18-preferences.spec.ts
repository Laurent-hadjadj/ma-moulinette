import { test, expect } from '../helpers/fixtures';
import type { Page } from '@playwright/test';
import { login } from '../helpers/auth';
import { resetAndSeedForPreferences } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 18 — Page Préférences (3 interrupteurs + 3 modales).
 *
 * Acteur : Nathan (ROLE_UTILISATEUR implicite, aucun rôle spécifique requis
 *   pour cette page). Le seed peuple ses préférences pour tetris:TetrisGame
 *   sur les 3 catégories (suivi_projet, favori_projet, favori_version avec
 *   2 versions) — sans ce seed, les listes sont vides et les 3 modales
 *   n'auraient rien à afficher/supprimer.
 *
 * Le bouton "Accéder à l'inventaire Actuator" est vérifié en négatif : le
 * seed (post-spec 05) ne donne pas ROLE_ACTUATOR à Nathan sur cette base.
 */
async function ouvrirModalePreference(page: Page, infoIconId: string, modalId: string): Promise<void> {
  // Le clic est bind par le module ES au chargement de la page (pas de délai
  // dynamique connu comme pour spec 07), mais on attend quand même le bind
  // jQuery par prudence — même précaution que ouvrirModaleVersions() du
  // spec 07 pour éviter un clic "mort" avant l'exécution du module.
  await page.waitForFunction((id) => {
    // Le bundle preference n'expose que `window.$` (pas `window.jQuery`).
    const w = window as unknown as { $?: any };
    const el = document.getElementById(id);
    if (!w.$ || !el) return false;
    const events = w.$._data(el, 'events');
    return !!events?.click?.length;
  }, infoIconId.replace('#', ''));

  await page.locator(infoIconId).click();
  await expect(page.locator(modalId)).toBeVisible();
}

test.describe('18 — Préférences', () => {
  test.beforeAll(() => {
    resetAndSeedForPreferences();
  });

  // 120s : 3 page.reload() dans ce spec, et le serveur de dev mono-worker
  // sert les nombreux modules ES (importmap) séquentiellement à chaque
  // rechargement (même constat que spec 07).
  test.setTimeout(120_000);

  test('Nathan consulte et modifie ses préférences', async ({ page }) => {
    await login(page, USERS.nathan);
    await page.goto('/preferences');
    await expect(page).toHaveURL(/\/preferences$/);

    await test.step('1. Pas de lien Actuator (ROLE_ACTUATOR absent)', async () => {
      await expect(page.locator('#bouton-ouvrir-actuator')).toHaveCount(0);
    });

    await test.step('2. Les 3 interrupteurs sont activés (seed statut=true)', async () => {
      await expect(page.locator('#js-switch-projet')).toBeChecked();
      await expect(page.locator('#js-switch-favori')).toBeChecked();
      await expect(page.locator('#js-switch-version')).toBeChecked();
    });

    await test.step('3. Modale Projets (lecture seule)', async () => {
      await ouvrirModalePreference(page, '#js-projet', '#modal-preference-projet');
      await expect(page.locator('#js-modal-projet-statut')).toContainText('Activée.');
      await expect(page.locator('#tableau-liste-projet')).toContainText('tetris:TetrisGame');
      await page.locator('#bouton-fermer-preference-projet').click();
      await expect(page.locator('#modal-preference-projet')).toBeHidden();
    });

    await test.step('4. Modale Favoris — suppression', async () => {
      await ouvrirModalePreference(page, '#js-favori', '#modal-preference-favori');
      await expect(page.locator('#js-modal-favori-statut')).toContainText('Activée.');
      await expect(page.locator('#mavenkey-favori-1')).toHaveText('tetris:TetrisGame');

      await page.locator('#poubelle-favori-1').click();
      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText('Le favori a bien été supprimé.');
      await expect(page.locator('#ligne-favori-1')).toBeHidden();

      await page.locator('#bouton-fermer-preference-favori').click();

      // Persistance réelle : reload + réouverture, la liste des favoris est vide.
      await page.reload({ waitUntil: 'domcontentloaded' });
      await ouvrirModalePreference(page, '#js-favori', '#modal-preference-favori');
      await expect(page.locator('#js-modal-favori-statut')).toContainText('Désactivée.');
      await page.locator('#bouton-fermer-preference-favori').click();
    });

    await test.step('5. Modale Versions — suppression d\'une version', async () => {
      await ouvrirModalePreference(page, '#js-version', '#modal-preference-version');
      await expect(page.locator('#js-modal-version-statut')).toContainText('Activée.');
      // .accordion-custom : classe propre à l'accordéon injecté par version()
      // (le reste de la page a d'autres accordéons Foundation, ex. le menu).
      await expect(page.locator('.accordion-custom')).toContainText('tetris:TetrisGame');

      // Le contenu de l'accordéon (lignes de version) est masqué par
      // Foundation tant que le titre n'a pas été cliqué (accordéon fermé
      // par défaut après `.foundation('_init')`).
      await page.locator('.accordion-custom').click();
      await expect(page.locator('#version-11')).toBeVisible();
      await expect(page.locator('#version-11')).toHaveText('1.0.0-RELEASE');
      await expect(page.locator('#version-12')).toHaveText('1.1.0-RELEASE');

      await page.locator('#poubelle-version-12').click();
      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText(
        'La version a bien été supprimée des favoris.'
      );
      await expect(page.locator('#ligne-version-12')).toBeHidden();

      await page.locator('#bouton-fermer-preference-version').click();

      // Persistance réelle : reload + réouverture, seule 1.0.0-RELEASE reste.
      await page.reload({ waitUntil: 'domcontentloaded' });
      await ouvrirModalePreference(page, '#js-version', '#modal-preference-version');
      await page.locator('.accordion-custom').click();
      await expect(page.locator('#version-11')).toHaveText('1.0.0-RELEASE');
      await expect(page.locator('td[id^="version-"]')).toHaveCount(1);
      await page.locator('#bouton-fermer-preference-version').click();
    });

    await test.step('6. Bascule interrupteur Projet — persistance réelle', async () => {
      await page.locator('label[for="js-switch-projet"]').click();
      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText(
        'La préférence a bien été mise à jour.'
      );

      await page.reload({ waitUntil: 'domcontentloaded' });
      await expect(page.locator('#js-switch-projet')).not.toBeChecked();
    });
  });
});
