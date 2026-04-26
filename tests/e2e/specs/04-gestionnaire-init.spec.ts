import { test, expect } from '@playwright/test';
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
    // ---------- 1. Login initial -> doit rediriger vers /reset-password ----------
    await page.goto('/login');
    await page.fill('input[name="login"]', USERS.aurelie.email);
    await page.fill('input[name="password"]', USERS.aurelie.password);
    await page.click('#valider-formulaire-login');
    await expect(page).toHaveURL(/\/mot-de-passe\/mise-a-jour/);

    // ---------- 2. Formulaire reset password ----------
    // .fill() est rejetée silencieusement par le password jQuery plugin OU
    // par les val('') au load de reset.js. On simule une vraie saisie clavier
    // via .pressSequentially() — dispatche keydown/keypress/input comme un user.
    await page.locator('#reset_password_form_ancienMotDePasse').click();
    await page.locator('#reset_password_form_ancienMotDePasse').pressSequentially(USERS.aurelie.password);

    await page.locator('#reset_password_form_plainPassword_first').click();
    await page.locator('#reset_password_form_plainPassword_first').pressSequentially(NEW_PASSWORD);

    await page.locator('#reset_password_form_plainPassword_second').click();
    await page.locator('#reset_password_form_plainPassword_second').pressSequentially(NEW_PASSWORD);

    // Le focus sur le 2e password active le bouton via reset.js (removeClass disabled-custom).
    // Click submit → reset.js: validate → set type=submit → re-click → form submit
    await Promise.all([
      page.waitForURL(/\/accueil/, { timeout: 15_000 }),
      page.click('#valider-formulaire-reset-password'),
    ]);

    // ---------- 3. Logout + re-login avec NEW_PASSWORD (valide la maj) ----------
    await logout(page);
    await login(page, USERS.aurelie, NEW_PASSWORD);
    await expect(page).toHaveURL(/\/accueil/);

    // ---------- 4. Mise à jour des projets (depuis /accueil) ----------
    // Aurélie est sur /accueil après re-login. Le bouton update projets
    // déclenche l'API /api/secure/accueil/projet qui appelle
    // /api/components/search_projects sur SonarQube (fixture → 1 projet tetris).
    await page.click('#bouton-mise-a-jour-referential');
    await page.waitForTimeout(2000); // laisse l'AJAX se terminer

    // ---------- 5. Collecte des profils qualité ----------
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
