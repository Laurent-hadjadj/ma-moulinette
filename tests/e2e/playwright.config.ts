import { defineConfig, devices } from '@playwright/test';

/**
 * Configuration Playwright pour Ma-Moulinette E2E (Phase K).
 *
 * Stratégie : scénario d'onboarding séquentiel.
 *   Chaque spec construit l'état pour la suivante (groupes → activations →
 *   collecte profils → affectations → collecte projet → suivi).
 *
 * Contraintes :
 *   - workers: 1 (état partagé en DB, pas de parallélisme)
 *   - fullyParallel: false (ordre garanti par tri alphabétique des fichiers)
 *   - DB clean obligatoire avant la 1ère spec : `bin/e2e/rebuild-database.ps1`
 *
 * ⚠️  CRITIQUE — Lancer le serveur Symfony AVEC APP_ENV=test (sinon écrit en PROD) :
 *
 *      $env:APP_ENV='test'; symfony serve --port=8000 --no-tls
 *
 *   Sans APP_ENV=test, Symfony charge .env.local → DATABASE_URL=ma_moulinette
 *   (PROD) au lieu de ma_moulinette_test. Toute action UI Playwright écrirait
 *   alors dans la base de prod (incident 2026-05-02 : marqueurs E2E retrouvés
 *   en prod avant correction). Les helpers DB côté tests forcent désormais
 *   -DbName ma_moulinette_test (cf. helpers/db.ts) — mais le serveur HTTP doit
 *   être démarré correctement pour que les inserts via UI atterrissent au bon
 *   endroit.
 *
 *   APP_ENV=test active aussi SonarFixtureClientService (pas de vrai SonarQube
 *   requis).
 */
export default defineConfig({
  testDir: './specs',

  // 60s par test (l'onboarding peut être long)
  timeout: 60_000,
  expect: { timeout: 5_000 },

  // Sequential : un seul worker, ordre garanti
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,

  // Retries : 0 par défaut (on veut voir le 1er échec immédiatement)
  retries: process.env.CI ? 1 : 0,

  reporter: [
    ['html', { open: 'never' }],
    ['list'],
  ],

  use: {
    baseURL: process.env.E2E_BASE_URL || 'http://localhost:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    locale: 'fr-FR',
    timezoneId: 'Europe/Paris',
    // Symfony login utilise un cookie de session standard
    storageState: undefined,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
