import { test as base, expect } from '@playwright/test';

/**
 * Étend le test de base pour bloquer les requêtes d'images (avatars, icônes…).
 * Ces requêtes sont purement cosmétiques et ne sont vérifiées par aucun spec ;
 * les bloquer réduit la charge sur le serveur Symfony de dev (mono/faible
 * parallélisme) et accélère les runs sans changer le comportement testé.
 */
export const test = base.extend({
  page: async ({ page }, use) => {
    await page.route(/\.(png|jpe?g|gif|svg|webp|ico)(\?.*)?$/i, (route) => route.abort());
    await use(page);
  },
});

export { expect };
