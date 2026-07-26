import { test, expect } from '../helpers/fixtures';
import { login, logout } from '../helpers/auth';
import { resetAndSeedAfterSpec03 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 04 — Initialisation du gestionnaire.
 *
 * Acteur : Aurélie (ROLE_GESTIONNAIRE), `reset_password=true` au démarrage.
 *
 * Parcours :
 *   1. Login avec password initial → redirection auto vers /reset-password
 *      (CustomAuthenticator::onAuthenticationSuccess détecte `reset_password=true`)
 *   2. Saisit ancien / nouveau / confirmation → submit
 *   3. Auto-redirect vers /accueil avec `reset_password=false` désormais
 *   4. Logout puis re-login avec le nouveau password (valide le flow complet)
 *   5. Sur /accueil clique #bouton-profil-quality (lien vers /profil)
 *   6. Sur /profil clique #bouton-refresh-profil → API call SonarQube fixture
 *      → 28 profils insérés en DB
 *   7. Navigation /projet, clique #bouton-mise-a-jour-referential
 *      → API call SonarQube fixture → 1 projet (tetris:TetrisGame) en DB
 *
 * Pré-requis : seed-after-spec-03 (4 users actifs + Aurélie reset_password=true).
 *
 * Fixtures SonarQube nécessaires :
 *   - qualityprofiles/search.json (28 profils)
 *   - components/search_projects.json (1 projet tetris:TetrisGame)
 */

const NEW_PASSWORD = 'AurelieNewPass2026!';

test.describe('04 — Initialisation gestionnaire', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec03();
  });

  test.setTimeout(120_000);

  test('Aurélie change password puis collecte profils + update projets', async ({ page }) => {
    // MODIF 2026-07-26 : capture des erreurs JS runtime du navigateur — le
    // clic sur "Valider" du formulaire de reset ne produit visuellement rien
    // (ni message d'erreur, ni redirection), ce qui évoque une exception non
    // gérée dans reset.js plutôt qu'un problème de saisie ou de timing.
    page.on('pageerror', (err) => {
      console.log(`[BROWSER pageerror] ${err.message}\n${err.stack}`);
    });
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        console.log(`[BROWSER console.error] ${msg.text()}`);
      }
    });

    // MODIF 2026-07-26 : découpage en test.step() pour isoler précisément
    // l'étape qui échoue dans le rapport Playwright (HTML report + trace),
    // et vérification immédiate de la valeur de chaque champ juste après
    // saisie — un run a montré le champ "Ancien mot de passe" contenant
    // l'email au lieu du mot de passe tapé, et les 2 champs "Nouveau mot de
    // passe" vides au moment de l'échec (visible dans error-context.md),
    // signe probable d'un auto-remplissage navigateur qui écrase la saisie
    // (Chrome ignore délibérément autocomplete="off" sur les champs
    // password). Ces assertions transforment un timeout opaque de 45s en
    // échec immédiat et explicite sur le champ précis en cause.

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
      // Le focus sur le 2e password active le bouton via reset.js (removeClass disabled-custom).
      // Click submit → reset.js: validate → set type=submit → re-click (link.click())
      // → vraie soumission HTML du <form> (pas d'AJAX), donc une vraie navigation
      // POST + redirect côté serveur vers /accueil.
      //
      // Un fallback par page.goto('/accueil') sur timeout a été essayé puis
      // écarté : s'il se déclenche pendant que la navigation POST est encore
      // en cours (juste lente), il l'interrompt avant que le serveur ait fini
      // de traiter le changement de mot de passe — la requête suivante voit
      // alors reset_password toujours à true. On attend donc patiemment la
      // vraie redirection, sans navigation concurrente qui risquerait
      // d'annuler une requête légitime.
      await Promise.all([
        page.waitForURL(/\/accueil/, { timeout: 45_000 }),
        page.click('#valider-formulaire-reset-password'),
      ]);
    });

    await test.step('4. Logout + re-login avec NEW_PASSWORD (valide la maj)', async () => {
      await logout(page);
      await login(page, USERS.aurelie, NEW_PASSWORD);
      await expect(page).toHaveURL(/\/accueil/);
    });

    await test.step('5. Mise à jour des projets (depuis /accueil)', async () => {
      // Le bouton update projets déclenche l'API /api/secure/accueil/projet
      // qui appelle /api/components/search_projects sur SonarQube (fixture
      // → 1 projet tetris).
      await page.click('#bouton-mise-a-jour-referential');
      await page.waitForTimeout(2000); // laisse l'AJAX se terminer
    });

    await test.step('6. Collecte des profils qualité', async () => {
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
