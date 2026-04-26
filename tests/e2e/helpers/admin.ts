import { Page } from '@playwright/test';

/**
 * Helpers de navigation dans le back-office EasyAdminBundle v4.
 *
 * L'app utilise les routes "pretty" (AdminRoute attribute) au lieu du
 * format legacy query-based `?crudAction=...&crudControllerFqcn=...`.
 *
 * Pattern : /admin/{entity-slug}[/{action}]
 *   - /admin/groupe-utilisateur          → index
 *   - /admin/groupe-utilisateur/new      → new
 *   - /admin/groupe-utilisateur/{id}/edit → edit
 */

export const ADMIN_ROUTES = {
  utilisateur:        '/admin/utilisateur',
  groupeUtilisateur:  '/admin/groupe-utilisateur',
  groupeFonctionnel:  '/admin/groupe-fonctionnel',
  portefeuille:       '/admin/portefeuille',
  batch:              '/admin/batch',
} as const;

export type AdminCrud = keyof typeof ADMIN_ROUTES;

export async function gotoCrudIndex(page: Page, crud: AdminCrud): Promise<void> {
  await page.goto(ADMIN_ROUTES[crud]);
}

export async function gotoCrudNew(page: Page, crud: AdminCrud): Promise<void> {
  await page.goto(`${ADMIN_ROUTES[crud]}/new`);
}
