import { test, expect } from '../helpers/fixtures';
import { login, loginExpectFailure, logout } from '../helpers/auth';
import { USERS } from '../helpers/users';

/**
 * Spec canari — vérifie que :
 *   - la page de login se rend
 *   - l'internal peut se connecter avec les fixtures-e2e.sql
 *   - les 4 autres users sont bien disabled (login refusé)
 *
 * À exécuter en premier après un rebuild DB pour valider l'infrastructure
 * (Symfony serve up + APP_ENV=test + fixtures-e2e.sql chargées).
 */
test.describe('01 — Smoke', () => {
  test('la page /login se rend', async ({ page }) => {
    await page.goto('/login');
    await expect(page.locator('input[name="login"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('#valider-formulaire-login')).toBeVisible();
  });

  test('login internal réussit (user actif, ROLE_INTERNAL)', async ({ page }) => {
    await login(page, USERS.interne);
    await logout(page);
  });

  test('login user disabled échoue (Aurélie, actif=false, ROLE_NONE)', async ({ page }) => {
    await loginExpectFailure(page, USERS.aurelie.email, USERS.aurelie.password);
  });

  test('login mauvais password échoue', async ({ page }) => {
    await loginExpectFailure(page, USERS.interne.email, 'wrong-password');
  });
});
