import { test, expect } from '../helpers/fixtures';
import { resetE2EData } from '../helpers/db';

/**
 * Spec 22 — Healthcheck (/api/public/healthcheck/status).
 *
 * Endpoint public (aucune route derrière un firewall authentifié) : pas de
 * login, aucune donnée e2e nécessaire au-delà d'une base accessible.
 * `HealthCheckService::check()` vérifie la connexion DB (`SELECT 1`) et
 * l'existence de la table `ma_moulinette.ma_moulinette` (table de version
 * applicative — pas le schéma), résultat mis en cache 5s.
 */
test.describe('22 — Healthcheck', () => {
  test.beforeAll(() => {
    resetE2EData();
  });

  test('statut nominal : base accessible → 200 OK', async ({ page }) => {
    const response = await page.request.get('/api/public/healthcheck/status');
    expect(response.status()).toBe(200);

    const body = await response.json();
    expect(body.codeRetour).toBe('OK');
    expect(body.listMessage).toEqual([]);
  });

  test('rate limiting : au-delà de 10 requêtes/minute par IP → 429', async ({ page }) => {
    // fixed_window, limit=10/minute, clé = IP cliente (config/packages/rate_limiter.yaml).
    // On boucle au-delà de la limite plutôt que de viser exactement la 11e
    // requête : robuste même si une requête précédente du run a déjà
    // consommé une partie du quota dans la même fenêtre.
    const statuses: number[] = [];
    for (let i = 0; i < 15; i++) {
      const response = await page.request.get('/api/public/healthcheck/status');
      statuses.push(response.status());
      if (response.status() === 429) {
        const body = await response.json();
        expect(body.codeRetour).toBe('KO');
        expect(body.listMessage[0]).toContain('tentatives dépassé');
        break;
      }
    }
    expect(statuses).toContain(429);
  });
});
