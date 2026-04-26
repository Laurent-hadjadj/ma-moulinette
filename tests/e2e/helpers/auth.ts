import { Page, expect } from '@playwright/test';
import type { E2EUser } from './users';

/**
 * Login d'un utilisateur via le formulaire /login.
 *
 * @param page    Page Playwright
 * @param user    Utilisateur E2E (depuis ./users.ts)
 * @param password Surchage optionnelle du password (utile pour tester un new
 *                 password après reset)
 */
export async function login(page: Page, user: E2EUser, password?: string): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="login"]', user.email);
  await page.fill('input[name="password"]', password ?? user.password);
  await page.click('#valider-formulaire-login');
  // Après login réussi, on est redirigé hors de /login.
  await expect(page).not.toHaveURL(/\/login/);
}

/**
 * Tente un login attendu en échec (user disabled, mauvais password, etc.)
 * Reste sur /login et vérifie qu'un message d'erreur s'affiche.
 */
export async function loginExpectFailure(page: Page, email: string, password: string): Promise<void> {
  await page.goto('/login');
  await page.fill('input[name="login"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('#valider-formulaire-login');
  await expect(page).toHaveURL(/\/login/);
}

/**
 * Logout via la route /logout.
 */
export async function logout(page: Page): Promise<void> {
  await page.goto('/logout');
  await expect(page).toHaveURL(/\/login/);
}
