import { test, expect } from '../helpers/fixtures';
import type { Page } from '@playwright/test';
import { login } from '../helpers/auth';
import { resetAndSeedAfterSpec05, resetAndSeedForSuiviHistorique } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 07 — Page Suivi (Sophie ROLE_COLLECTE+ROLE_SUIVI).
 *
 * Acteur : Sophie (ROLE_COLLECTE + ROLE_SUIVI)
 *
 * Parcours (test 1) :
 *   1. Login Sophie
 *   2. Navigate to /projet
 *   3. Sélectionne tetris:TetrisGame depuis #liste-projet
 *   4. Clique #bouton-tableau-de-bord → navigation vers /suivi/set?token=...
 *      (jeton ROT13+base64, depuis 2026-07-16) qui redirige sur /suivi
 *   5. Vérifie qu'on est bien sur la page /suivi
 *
 * Parcours (tests 2 et 3) : suppression d'historique + définition de la
 *   version de référence, sur un historique tetris seedé directement en
 *   base (2 versions) — voir resetAndSeedForSuiviHistorique() dans
 *   tests/e2e/helpers/db.ts pour le pourquoi (fixture SonarQube limitée à
 *   une seule analyse, insuffisant pour produire 2 versions via une vraie
 *   collecte).
 *
 * Pré-requis : seed-after-spec-05 (groupe fonctionnel + Sophie a accès au
 *   projet tetris via liste_groupe_fonctionnel = ["tetris-game"]).
 */

const PROJECT_KEY = 'tetris:TetrisGame';

/** Navigue jusqu'à /suivi pour tetris:TetrisGame (login + sélection projet + bouton tableau de bord). */
async function goToSuiviTetris(page: Page): Promise<void> {
  await login(page, USERS.sophie);

  await page.goto('/projet');
  await expect(page).toHaveURL(/\/projet/);

  await expect(page.locator(`#liste-projet option[value="${PROJECT_KEY}"]`))
    .toHaveCount(1, { timeout: 10_000 });
  await page.locator('#liste-projet').selectOption(PROJECT_KEY);
  await expect(page.locator('#select-result')).toContainText(PROJECT_KEY);

  const suiviBtn = page.locator('#bouton-tableau-de-bord');
  // Le bouton est désactivé tant qu'aucun projet n'est sélectionné. Après
  // sélection, le JS retire la classe disabled-bouton.
  await expect(suiviBtn).not.toHaveClass(/disabled-bouton/);
  await suiviBtn.click();

  await expect(page).toHaveURL(/\/suivi/, { timeout: 15_000 });
}

/**
 * Ouvre la modale "Modifier les paramètres" (charge la table des versions
 * via /api/secure/suivi/version/liste, cf. templates/suivi/_menu_outils.html.twig
 * + assets/js/mon-application/suivi/index-suivi.js:versionListe()).
 */
async function ouvrirModaleVersions(page: Page): Promise<void> {
  // Le bouton est rendu côté serveur (Twig) mais son handler de clic n'est attaché
  // qu'une fois le module index-suivi.js chargé/exécuté (import ESM asynchrone) —
  // sans cette attente, Playwright peut cliquer avant que le handler jQuery soit
  // bind, et le clic ne déclenche alors rien (pas d'appel réseau, pas d'erreur JS).
  await page.waitForFunction(() => {
    const w = window as unknown as { jQuery?: any };
    const el = document.querySelector('.js-modifier-analyse');
    if (!w.jQuery || !el) return false;
    const events = w.jQuery._data(el, 'events');
    return !!events?.click?.length;
  });

  await page.locator('.js-modifier-analyse').click();
  await expect(page.locator('#modal-modifier-analyse')).toBeVisible();
  await expect(page.locator('#tableau-liste-version tr[id^="ligne-"]')).not.toHaveCount(0, { timeout: 10_000 });
}

/** Retrouve le numéro de "ligne" (id="ligne-N") de la version donnée dans la table de la modale. */
async function ligneDeLaVersion(page: Page, version: string): Promise<number> {
  const rows = page.locator('#tableau-liste-version tr[id^="ligne-"]');
  const count = await rows.count();
  for (let i = 0; i < count; i++) {
    const row = rows.nth(i);
    const versionText = (await row.locator('td[id^="version-"]').innerText()).trim();
    if (versionText === version) {
      const id = await row.getAttribute('id');
      return Number(id?.split('-')[1]);
    }
  }
  throw new Error(`Version ${version} introuvable dans la table de suivi.`);
}

test.describe('07 — Page Suivi', () => {
  // MODIF : 90s (au lieu de 60s) — les tests "Modifier les versions" font un
  // page.reload() après l'action AJAX ; le serveur de dev mono-worker sert les
  // nombreux modules ES (importmap) séquentiellement, ce qui peut dépasser 60s.
  test.setTimeout(90_000);

  test.describe('Navigation', () => {
    test.beforeAll(() => {
      resetAndSeedAfterSpec05();
    });

    test('Sophie accède à la page de suivi pour tetris', async ({ page }) => {
      await goToSuiviTetris(page);
    });
  });

  test.describe('Modifier les versions (suppression + référence)', () => {
    test.beforeAll(() => {
      resetAndSeedForSuiviHistorique();
    });

    test('Sophie définit une nouvelle version de référence', async ({ page }) => {
      await goToSuiviTetris(page);
      await ouvrirModaleVersions(page);

      // 1.1.0-RELEASE n'est pas encore la version de référence (initial=false en seed).
      const ligne = await ligneDeLaVersion(page, '1.1.0-RELEASE');
      const switchReference = page.locator(`#switch-reference-${ligne}`);
      await expect(switchReference).not.toBeChecked();

      // Le "switch" est un input caché stylé via son <label> (switch-paddle) qui le
      // recouvre visuellement — cliquer l'input directement échoue ("intercepts
      // pointer events"). Le label déclenche nativement le clic sur l'input associé.
      await page.locator(`label[for="switch-reference-${ligne}"]`).click();

      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText(
        'Mise a jour de la version de reference effectuée.'
      );

      // Persistance réelle (pas juste optimiste côté client) : reload + réouverture.
      await page.reload({ waitUntil: 'domcontentloaded' });
      await ouvrirModaleVersions(page);
      const ligneApresReload = await ligneDeLaVersion(page, '1.1.0-RELEASE');
      await expect(page.locator(`#switch-reference-${ligneApresReload}`)).toBeChecked();
    });

    test('Sophie supprime une version de l\'historique', async ({ page }) => {
      await goToSuiviTetris(page);
      await ouvrirModaleVersions(page);

      const ligne = await ligneDeLaVersion(page, '1.0.0-RELEASE');
      await page.locator(`#poubelle-${ligne}`).click();

      await expect(page.locator('#message-box')).not.toHaveClass(/hide/);
      await expect(page.locator('#message-text')).toContainText(
        'Le projet a été correctement supprimé.'
      );
      await expect(page.locator(`#ligne-${ligne}`)).toBeHidden();

      // Persistance réelle : reload + réouverture, la version supprimée ne doit plus apparaître.
      await page.reload({ waitUntil: 'domcontentloaded' });
      await ouvrirModaleVersions(page);
      await expect(page.locator('#tableau-liste-version td[id^="version-"]')).toHaveText(['1.1.0-RELEASE']);
    });
  });

  test.describe('Graphique "Historique des anomalies" (régression tooltipFormat)', () => {
    test.beforeAll(() => {
      resetAndSeedForSuiviHistorique();
    });

    /**
     * Régression 2026-11-07 : tooltipFormat: 'll' (token Moment.js, invalide
     * en date-fns — l'adaptateur réellement utilisé ici) faisait planter
     * Chart.js avec un RangeError au survol d'un point du graphique
     * ("Format string contains an unescaped latin alphabet character `l`").
     * Le canvas se rend normalement au chargement (dessineMoiUnMouton()
     * s'exécute sans garde dès le chargement du module) — seule l'INTERACTION
     * (tooltip → getLabelForValue() → date-fns format()) déclenchait l'erreur,
     * donc une simple assertion de visibilité du canvas ne l'aurait jamais
     * détectée.
     */
    test('Le survol du graphique ne déclenche aucune erreur JS', async ({ page }) => {
      const pageErrors: Error[] = [];
      page.on('pageerror', (err) => pageErrors.push(err));

      await goToSuiviTetris(page);

      const canvas = page.locator('#graphique-anomalie');
      await expect(canvas).toBeVisible();

      const box = await canvas.boundingBox();
      expect(box).not.toBeNull();
      if (box) {
        await page.mouse.move(box.x + box.width * 0.3, box.y + box.height / 2);
        await page.mouse.move(box.x + box.width * 0.7, box.y + box.height / 2);
      }

      expect(pageErrors.map((e) => e.message)).toEqual([]);
    });
  });

  test.describe('Rechargement automatique à la fermeture des modales (régression)', () => {
    test.beforeAll(() => {
      resetAndSeedForSuiviHistorique();
    });

    /**
     * Régression 2026-11-07 : la page (compteur, graphiques, tableaux —
     * tous rendus côté serveur) ne se mettait jamais à jour après une action
     * dans une modale, sans aucun test de régression jusqu'ici (comportement
     * navigateur — un vrai rechargement de page — invisible à Unit et
     * Integration). Vérifie qu'un événement `load` se déclenche réellement
     * à la fermeture de la modale "Modifier les versions", quel que soit le
     * chemin de fermeture (ici : le bouton "Fermer").
     */
    test('La fermeture de la modale "Modifier" recharge réellement la page', async ({ page }) => {
      await goToSuiviTetris(page);
      await ouvrirModaleVersions(page);

      await Promise.all([
        page.waitForEvent('load', { timeout: 10_000 }),
        page.locator('#bouton-fermer-modifier-analyse').click(),
      ]);
    });
  });
});
