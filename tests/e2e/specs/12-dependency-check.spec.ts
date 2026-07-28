import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedForDependencyCheck } from '../helpers/db';
import { processDcQueue, buildTetrisDcReport, DC_INGEST_TOKEN } from '../helpers/dc';
import { USERS } from '../helpers/users';

/**
 * Spec 12 — Module Dependency-Check (ingestion + consultation).
 *
 * Acteur : Nathan (ROLE_SECURITY, cumulé via resetAndSeedForDependencyCheck()
 *   — les 5 users e2e du récit d'onboarding n'ont nativement aucun rôle
 *   Dependency-Check, ce module est transverse comme spec 08/09/10).
 *
 * L'ingestion est asynchrone en 2 étapes, pas un simple POST :
 *   1. POST /api/secure/dependency-check/upload (PUBLIC_ACCESS, sécurisé par
 *      le header X-DependencyCheck-Token, pas la session Symfony) → enqueue
 *      en base, réponse 202 + ulid, statut "queued".
 *   2. Un worker séparé (`bin/console app:dependency-check:process`) doit
 *      tourner pour transformer la queue en dc_scan/dc_finding/dc_dependency/
 *      dc_cve — rien n'affiche encore quoi que ce soit tant qu'il n'a pas
 *      tourné. Aucun cron n'est démarré par la stack e2e (contrairement à la
 *      prod) : ce spec invoque le worker directement via processDcQueue()
 *      (bin/e2e/process-dc-queue.ps1, APP_ENV=test) après l'upload.
 *
 * Pas d'équivalent SonarFixtureClientService ici : l'ingestion est un appel
 * ENTRANT (POST reçu par l'appli), pas un appel sortant à mocker — le POST
 * direct via page.request est donc un test fidèle du vrai flux CI.
 *
 * Pré-requis : le projet tetris:TetrisGame doit être dans le périmètre de
 * l'utilisateur (groupe fonctionnel "tetris-game") pour que /dependency-check
 * l'affiche — même mécanisme de filtrage que /projet (MesProjets::liste()).
 */
const REPORT = buildTetrisDcReport();

test.describe('12 — Dependency-Check', () => {
  test.beforeAll(() => {
    resetAndSeedForDependencyCheck();
  });

  test.setTimeout(90_000);

  test('Ingestion CI puis consultation par Nathan', async ({ page }) => {
    await test.step('1. Upload du rapport (simulateur CI, pas de session)', async () => {
      const response = await page.request.post('/api/secure/dependency-check/upload', {
        headers: {
          'Content-Type': 'application/json',
          'X-DependencyCheck-Token': DC_INGEST_TOKEN,
        },
        data: JSON.stringify(REPORT),
      });
      expect(response.status()).toBe(202);
      const body = await response.json();
      expect(body.status).toBe('queued');
    });

    await test.step('2. Traitement du worker (queue → dc_scan/dc_finding)', async () => {
      processDcQueue();
    });

    await test.step('3. Nathan consulte la liste des projets scannés', async () => {
      await login(page, USERS.nathan);
      await page.goto('/dependency-check');
      await expect(page).toHaveURL(/\/dependency-check$/);

      const row = page.locator('table tbody tr', { hasText: 'TetrisGame' });
      await expect(row).toHaveCount(1);
      await expect(row).toContainText('tetris');
      await expect(row).toContainText('1.1.0-RELEASE');
      await expect(row.locator('.badge-count.high')).toHaveText('1');
    });

    await test.step('4. Détail du scan : la CVE ingérée est bien visible', async () => {
      const row = page.locator('table tbody tr', { hasText: 'TetrisGame' });
      await row.getByRole('link', { name: 'Detail' }).click();
      await expect(page).toHaveURL(/\/dependency-check\/projet\/tetris\/TetrisGame\/1\.1\.0-RELEASE/);
      await expect(page.locator('body')).toContainText('CVE-2023-46120');
    });

    await test.step('5. Dashboard agrégé accessible sans erreur', async () => {
      await page.goto('/dependency-check/dashboard');
      await expect(page).toHaveURL(/\/dependency-check\/dashboard/);
      await expect(page.locator('.badge-scope')).toContainText('tetris-game');
    });
  });
});
