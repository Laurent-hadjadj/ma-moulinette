import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedAfterSpec03, resetAndSeedAfterSpec04, resetAndSeedAfterSpec08 } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 08 — Contrôle d'accès par rôle.
 *
 * Deux mécanismes distincts cohabitent dans l'app (cf. mkDocs/docs/
 * ma-moulinette/developpement/test-e2e.md, section "Conclusion pratique
 * e2e") :
 *
 *   A. Contrôle STRICT — `#[IsGranted(...)]` (classe ou méthode) : lève une
 *      vraie AccessDeniedException → vrai HTTP 403. Concerne ROLE_INTERNAL
 *      (/admin/logs), ROLE_GESTIONNAIRE (actions accueil), ROLE_SECURITY
 *      (/dependency-check), ROLE_ACTIVITY (/activity).
 *
 *   B. Contrôle SOUPLE — `isGranted()` manuel dans le corps du contrôleur :
 *      pas d'exception, juste un flash message + rendu normal, HTTP 200.
 *      Concerne ROLE_BATCH (/traitement/suivi) et ROLE_ACTUATOR (/actuator).
 *      Sélecteur du flash : `.js-flash-box .callout-message`
 *      (templates/_message.html.twig).
 *
 * ROLE_SECURITY_ANALYTICS n'est PAS un contrôle d'accès : c'est un
 * feature-flag d'affichage (vue restreinte vs vue org-wide) à l'intérieur
 * de pages déjà protégées par ROLE_SECURITY — aucun accès n'est refusé
 * sans lui. Hors périmètre de ce spec (accès), à couvrir séparément comme
 * test fonctionnel si besoin.
 *
 * La page d'erreur personnalisée (App\Controller\ErrorController) n'est
 * activée que `when@prod` : en dev/test c'est la page d'exception standard
 * de Symfony qui s'affiche pour le cas A. On vérifie donc le code HTTP,
 * pas le rendu visuel (dépendant de l'environnement).
 *
 * Pré-requis :
 *   - Cas A (sauf ROLE_GESTIONNAIRE positif) + Cas B négatifs : seed post-
 *     spec-03 suffit (Josh=ROLE_UTILISATEUR, Nathan=ROLE_COLLECTE,
 *     Sophie=ROLE_COLLECTE+ROLE_SUIVI, Aurélie=ROLE_GESTIONNAIRE,
 *     interne=ROLE_INTERNAL).
 *   - Cas A/B positifs pour ROLE_SECURITY/ROLE_ACTIVITY/ROLE_BATCH/
 *     ROLE_ACTUATOR (rôles qu'aucun user ne porte nativement dans le
 *     scénario 01-07) : seed dédié resetAndSeedAfterSpec08() qui cumule
 *     ces 4 rôles sur Nathan (cf. helpers/db.ts).
 */

/**
 * `App\EventSubscriber\ApiClientHeaderSubscriber` bloque en 403 toute
 * requête vers /api/secure/* qui n'a pas un Origin/Referer autorisé
 * (APP_ALLOWED_ORIGINS) ET le header X-Internal-Front: front-app — AVANT
 * même que le contrôleur/IsGranted ne soit atteint. Contrairement à un
 * vrai fetch() déclenché depuis la page, `page.request.post()` n'ajoute
 * pas ces en-têtes automatiquement : sans eux on obtient un 403 pour TOUS
 * les users (y compris ceux avec le bon rôle), ce qui ne testerait pas du
 * tout ROLE_GESTIONNAIRE. On les ajoute explicitement pour isoler le
 * contrôle de rôle comme seule variable testée.
 */
const INTERNAL_FRONT_HEADERS = {
  Origin: 'http://localhost:8000',
  'X-Internal-Front': 'front-app',
};

test.describe("08 — Contrôle d'accès par rôle", () => {
  test.describe('A. Contrôle strict (#[IsGranted], 403 réel)', () => {
    test.beforeAll(() => {
      resetAndSeedAfterSpec03();
    });

    test('Josh (ROLE_UTILISATEUR seul) reçoit un 403 sur /admin/logs (ROLE_INTERNAL requis)', async ({ page }) => {
      await login(page, USERS.josh);
      const response = await page.goto('/admin/logs');
      expect(response?.status()).toBe(403);
    });

    test('interne (ROLE_INTERNAL) accède normalement à /admin/logs', async ({ page }) => {
      await login(page, USERS.interne);
      const response = await page.goto('/admin/logs');
      expect(response?.status()).toBe(200);
    });

    test('Nathan (ROLE_COLLECTE) reçoit un 403 sur les actions accueil (ROLE_GESTIONNAIRE requis)', async ({ page }) => {
      await login(page, USERS.nathan);
      const listeResponse = await page.request.post('/api/secure/accueil/projet', { headers: INTERNAL_FRONT_HEADERS });
      expect(listeResponse.status()).toBe(403);
      const tagsResponse = await page.request.post('/api/secure/accueil/tags', { headers: INTERNAL_FRONT_HEADERS });
      expect(tagsResponse.status()).toBe(403);
    });

    test.describe('Aurélie positif (reset password déjà effectué)', () => {
      test.beforeAll(() => {
        // resetAndSeedAfterSpec03() laisse Aurélie avec reset_password=true
        // (flow nominal 1ère connexion, testé en spec 04) : tant que ce flag
        // est actif, le gate reset-password bloque l'accès à toute route hors
        // /mot-de-passe/mise-a-jour et /logout, quel que soit son rôle — ce
        // n'est pas un manque de ROLE_GESTIONNAIRE. resetAndSeedAfterSpec04()
        // simule l'état post-reset (reset_password=false) nécessaire ici.
        resetAndSeedAfterSpec04();
      });

      test('Aurélie (ROLE_GESTIONNAIRE) accède normalement aux actions accueil', async ({ page }) => {
        await login(page, USERS.aurelie);
        const listeResponse = await page.request.post('/api/secure/accueil/projet', { headers: INTERNAL_FRONT_HEADERS });
        expect(listeResponse.status()).toBe(200);
        const tagsResponse = await page.request.post('/api/secure/accueil/tags', { headers: INTERNAL_FRONT_HEADERS });
        expect(tagsResponse.status()).toBe(200);
      });
    });

    test('Josh (sans ROLE_SECURITY) reçoit un 403 sur /dependency-check', async ({ page }) => {
      await login(page, USERS.josh);
      const response = await page.goto('/dependency-check');
      expect(response?.status()).toBe(403);
    });

    test('Josh (sans ROLE_ACTIVITY) reçoit un 403 sur /activity', async ({ page }) => {
      await login(page, USERS.josh);
      const response = await page.goto('/activity');
      expect(response?.status()).toBe(403);
    });

    test.describe('Cas positifs (rôles transverses cumulés sur Nathan)', () => {
      test.beforeAll(() => {
        resetAndSeedAfterSpec08();
      });

      test('Nathan (ROLE_SECURITY) accède normalement à /dependency-check', async ({ page }) => {
        await login(page, USERS.nathan);
        const response = await page.goto('/dependency-check');
        expect(response?.status()).toBe(200);
      });

      test('Nathan (ROLE_ACTIVITY) accède normalement à /activity', async ({ page }) => {
        await login(page, USERS.nathan);
        const response = await page.goto('/activity');
        expect(response?.status()).toBe(200);
      });
    });
  });

  test.describe('B. Contrôle souple (isGranted() manuel, flash + 200)', () => {
    test.beforeAll(() => {
      resetAndSeedAfterSpec03();
    });

    test("Sophie (sans ROLE_BATCH) voit le flash d'accès refusé sur /traitement/suivi", async ({ page }) => {
      await login(page, USERS.sophie);
      const response = await page.goto('/traitement/suivi');
      expect(response?.status()).toBe(200);
      await expect(page.locator('.js-flash-box .callout-message')).toContainText(
        "Vous devez avoir le rôle 'BATCH'"
      );
    });

    test("Sophie (sans ROLE_ACTUATOR) voit le flash d'accès refusé sur /actuator", async ({ page }) => {
      await login(page, USERS.sophie);
      const response = await page.goto('/actuator');
      expect(response?.status()).toBe(200);
      await expect(page.locator('.js-flash-box .callout-message')).toBeVisible();
    });

    test.describe('Cas positifs (rôles transverses cumulés sur Nathan)', () => {
      test.beforeAll(() => {
        resetAndSeedAfterSpec08();
      });

      test('Nathan (ROLE_BATCH) accède à /traitement/suivi sans le flash de refus', async ({ page }) => {
        await login(page, USERS.nathan);
        const response = await page.goto('/traitement/suivi');
        expect(response?.status()).toBe(200);
        // Pas de données en base fraîchement resetée -> flash "Aucun traitement
        // trouvé" légitime (le pendant "pas de données" attendu quand le rôle
        // est correct). On vérifie l'absence du message de REFUS spécifiquement,
        // pas l'absence de tout flash.
        await expect(page.locator('.js-flash-box .callout-message')).not.toContainText(
          "Vous devez avoir le rôle"
        );
      });

      test('Nathan (ROLE_ACTUATOR) accède à /actuator sans le flash de refus', async ({ page }) => {
        await login(page, USERS.nathan);
        const response = await page.goto('/actuator');
        expect(response?.status()).toBe(200);
        const flash = page.locator('.js-flash-box .callout-message');
        if (await flash.count() > 0) {
          await expect(flash).not.toContainText("Vous devez avoir le rôle");
        }
      });
    });
  });
});
