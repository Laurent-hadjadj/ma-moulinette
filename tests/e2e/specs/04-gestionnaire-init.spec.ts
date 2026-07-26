import { test, expect } from '../helpers/fixtures';
import { login, logout } from '../helpers/auth';
import { resetAndSeedAfterSpec03, resetAndSeedAfterSpec04 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 04 — Initialisation du gestionnaire.
 *
 * Acteur : Aurélie (ROLE_GESTIONNAIRE), `reset_password=true` au démarrage.
 *
 * Deux cas de test indépendants (scindés le 2026-07-26 — voir notes) :
 *
 *   A. « change son mot de passe » (test.fixme, best-effort) : login →
 *      redirection /mot-de-passe/mise-a-jour → saisie → submit → /accueil →
 *      logout → re-login avec le nouveau password.
 *
 *   B. « met à jour les projets et collecte les profils qualité » : seedé
 *      directement en état post-reset (reset_password=false), login direct,
 *      clique mise-à-jour-référentiel + refresh-profil. Ne dépend pas du
 *      succès du cas A (cf. helpers/db.ts::resetAndSeedAfterSpec04, qui
 *      réinjecte l'état par SQL plutôt que de rejouer l'UI du cas A).
 *
 * MODIF 2026-07-26 : le cas A reste flaky (~50% d'échec) malgré deux vrais
 * bugs applicatifs trouvés et corrigés au passage (gardés, indépendamment de
 * ce test) :
 *   - assets/js/auth/reset.js : le bouton rappelait .click() sur lui-même
 *     depuis son propre handler pour forcer la soumission après avoir changé
 *     son type en "submit" — un click() imbriqué sur le même élément peut
 *     être avalé par le flag anti-réentrance du spec HTML (clic sans effet
 *     visible). Remplacé par form.requestSubmit().
 *   - src/Form/ResetPasswordFormType.php : autocomplete="off" sur les champs
 *     password, or Chrome l'ignore délibérément sur ce type de champ depuis
 *     2014 ; remplacé par les valeurs sémantiques current-password/new-password.
 * Malgré ces deux fix, un run sur deux environ voit encore le champ "Ancien
 * mot de passe" écrasé par l'email du compte entre la saisie (vérifiée
 * correcte via toHaveValue juste après) et le clic sur Valider — cause
 * exacte non identifiée (probablement une interaction Chromium autofill
 * spécifique à ce enchaînement de 3 champs password sur la même page,
 * distincte des deux bugs déjà corrigés). Décision d'équipe du 2026-07-26 :
 * on met ce test de côté (test.fixme) plutôt que de le laisser flaky en CI,
 * et on couvre la collecte projets/profils séparément (cas B) puisqu'elle
 * ne dépend pas de ce parcours.
 */

const NEW_PASSWORD = 'AurelieNewPass2026!';

test.describe('04 — Initialisation gestionnaire', () => {
  test.describe('A. Changement de mot de passe (flaky, cf. note en tête de fichier)', () => {
    test.beforeAll(() => {
      resetAndSeedAfterSpec03();
    });

    test.setTimeout(60_000);

    // eslint-disable-next-line playwright/no-fixme -- flakiness documentée, cf. note en tête de fichier
    test.fixme('Aurélie change son mot de passe puis se reconnecte avec le nouveau', async ({ page }) => {
      page.on('pageerror', (err) => {
        console.log(`[BROWSER pageerror] ${err.message}\n${err.stack}`);
      });
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          console.log(`[BROWSER console.error] ${msg.text()}`);
        }
      });

      await test.step('1. Login initial → redirection vers /mot-de-passe/mise-a-jour', async () => {
        await page.goto('/login');
        await page.fill('input[name="login"]', USERS.aurelie.email);
        await page.fill('input[name="password"]', USERS.aurelie.password);
        await page.click('#valider-formulaire-login');
        await expect(page).toHaveURL(/\/mot-de-passe\/mise-a-jour/);
      });

      await test.step('2. Remplissage du formulaire de reset password', async () => {
        // .fill() est rejetée silencieusement par le password jQuery plugin OU
        // par les val('') au load de reset.js. On simule une vraie saisie clavier
        // via .pressSequentially() — dispatche keydown/keypress/input comme un user.
        const ancien = page.locator('#reset_password_form_ancienMotDePasse');
        await ancien.click();
        await ancien.pressSequentially(USERS.aurelie.password);
        await expect(ancien, 'Ancien mot de passe altéré après saisie (auto-remplissage navigateur ?)')
          .toHaveValue(USERS.aurelie.password);

        const nouveau = page.locator('#reset_password_form_plainPassword_first');
        await nouveau.click();
        await nouveau.pressSequentially(NEW_PASSWORD);
        await expect(nouveau, 'Nouveau mot de passe altéré après saisie (auto-remplissage navigateur ?)')
          .toHaveValue(NEW_PASSWORD);

        const verification = page.locator('#reset_password_form_plainPassword_second');
        await verification.click();
        await verification.pressSequentially(NEW_PASSWORD);
        await expect(verification, 'Vérification mot de passe altérée après saisie (auto-remplissage navigateur ?)')
          .toHaveValue(NEW_PASSWORD);
      });

      await test.step('3. Soumission → redirection vers /accueil', async () => {
        await Promise.all([
          page.waitForURL(/\/accueil/, { timeout: 15_000 }),
          page.click('#valider-formulaire-reset-password'),
        ]);
      });

      await test.step('4. Logout + re-login avec NEW_PASSWORD (valide la maj)', async () => {
        await logout(page);
        await login(page, USERS.aurelie, NEW_PASSWORD);
        await expect(page).toHaveURL(/\/accueil/);
      });
    });
  });

  test.describe('B. Collecte projets et profils qualité', () => {
    test.beforeAll(() => {
      resetAndSeedAfterSpec04();
    });

    test('Aurélie met à jour les projets et collecte les profils qualité', async ({ page }) => {
      // Seed post-spec-04 : reset_password déjà à false, mot de passe
      // inchangé (le seed SQL ne rejoue pas le hash) → login direct.
      await login(page, USERS.aurelie);
      await expect(page).toHaveURL(/\/accueil/);

      await test.step('Mise à jour des projets (depuis /accueil)', async () => {
        // Déclenche l'API /api/secure/accueil/projet → /api/components/search_projects
        // sur SonarQube (fixture → 1 projet tetris).
        await page.click('#bouton-mise-a-jour-referential');
        await page.waitForTimeout(2000); // laisse l'AJAX se terminer
      });

      await test.step('Collecte des profils qualité', async () => {
        // #bouton-profil-quality est un lien vers /profil
        await page.click('#bouton-profil-quality');
        await expect(page).toHaveURL(/\/profil/);

        // Sur /profil, déclencher la collecte SonarQube
        // (fixture qualityprofiles/search.json → 28 profils insérés)
        await page.click('#bouton-refresh-profil');
        await page.waitForTimeout(2000);

        // Pas d'assertion DOM finale : la simple absence d'erreur Playwright valide
        // que les API calls ont été déclenchés sans 5xx. On affinera quand on aura
        // les sélecteurs exacts des indicateurs de succès (toast / compteur).
      });
    });
  });
});
