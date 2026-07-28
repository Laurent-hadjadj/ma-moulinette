import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedAfterSpec05 } from '../helpers/db';
import { buildProjetToken } from '../helpers/token';
import { USERS } from '../helpers/users';

/**
 * Spec 13 — Module Clean Code (dashboard 1 projet + synthèse portefeuille).
 *
 * Acteur : Nathan (ROLE_COLLECTE, aucun rôle transverse nécessaire — même
 *   périmètre tetris-game que spec 06/11).
 *
 * Comme OWASP (spec 11), la collecte Clean Code n'est pas déclenchable
 * isolément : elle fait partie de l'orchestration unique de collecte
 * générale (`BatchCollecteCleanCodeController`, appelé depuis
 * `ApiCollecteController::apiCollecteAnomalie()`), silencieuse si le
 * serveur SonarQube ne supporte pas les facettes 10+. Ce spec rejoue donc
 * le flux complet de spec 06 avant de vérifier `/clean-code`.
 *
 * Contrairement à OWASP, le bouton "Clean Code" sur /projet est masqué en
 * e2e (`version_serveur_sonar` simulé = 8, cf. SONAR_VERSION en
 * .env.test.local — changer cette valeur globale risquerait de perturber
 * d'autres specs qui dépendent du comportement v8). Le token
 * (rot13+base64 de `salt|maven_key`) est donc reconstruit côté test via
 * buildProjetToken() (tests/e2e/helpers/token.ts) pour naviguer
 * directement vers /clean-code?token=..., sans dépendre du bouton masqué.
 *
 * Fixture enrichie : tests/fixtures/sonarqube/issues/search.json avait
 * `"facets": []` — insuffisant pour produire des indicateurs clean code
 * exploitables (tout à zéro). Enrichi avec cleanCodeAttributeCategories/
 * impactSeverities/impactSoftwareQualities (valeurs produisant un niveau
 * de risque "medium", une gouvernance RESPONSIBLE >5% et une exposition
 * sécurité ~13%) sans toucher paging.total, dont dépendent déjà les
 * compteurs OWASP/SANS des autres specs (aucune régression).
 *
 * Pré-requis : seed post-spec-05 (groupe fonctionnel + projet tetris dans
 * le périmètre de Nathan), comme spec 06/11.
 */
const PROJECT_KEY = 'tetris:TetrisGame';

test.describe('13 — Clean Code', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec05();
  });

  test.setTimeout(120_000);

  test('Nathan lance la collecte puis consulte les dashboards Clean Code', async ({ page }) => {
    await login(page, USERS.nathan);

    await test.step('1. Collecte + affichage + enregistrement (identique spec 06)', async () => {
      // La page /clean-code/synthese lit historique (via
      // selectHistoriqueCleanCodeSynthese) — pas seulement clean_code. Sans
      // l'étape "Enregistrer", aucune ligne historique n'existe et la page
      // resterait vide (503) : on rejoue donc le flux complet de spec 06, pas
      // seulement la collecte.
      await page.goto('/projet');
      await expect(page).toHaveURL(/\/projet/);

      await expect(page.locator(`#liste-projet option[value="${PROJECT_KEY}"]`))
        .toHaveCount(1, { timeout: 10_000 });
      await page.locator('#liste-projet').selectOption(PROJECT_KEY);
      await expect(page.locator('#select-result')).toContainText(PROJECT_KEY);

      const collecteBtn = page.locator('#bouton-collecte-indicateur');
      await expect(collecteBtn).not.toHaveClass(/disabled-bouton/);
      await collecteBtn.click();

      await expect(page.locator('#log')).toHaveValue(/\(\d+\) La collecte des données est terminée/, {
        timeout: 90_000,
      });

      const afficheBtn = page.locator('#bouton-affiche-indicateur');
      await expect(afficheBtn).toBeVisible();
      await afficheBtn.click();
      await page.waitForTimeout(5_000);

      const enregistreBtn = page.locator('#bouton-enregistrement-indicateur');
      await expect(enregistreBtn).toBeVisible();
      const [enregistrementResponse] = await Promise.all([
        page.waitForResponse((r) => r.url().includes('/api/secure/enregistrement'), { timeout: 10_000 }),
        enregistreBtn.click(),
      ]);
      expect(enregistrementResponse.status()).toBe(200);
    });

    await test.step('2. Dashboard /clean-code (1 projet)', async () => {
      const token = buildProjetToken(PROJECT_KEY);
      await page.goto(`/clean-code?token=${token}`);
      await expect(page).toHaveURL(/\/clean-code\?token=/);

      // Pas l'état "Aucune donnée disponible" — la collecte a bien produit une ligne clean_code.
      await expect(page.locator('.callout.warning', { hasText: 'Aucune donnée disponible' })).toHaveCount(0);

      // Score de risque : 1,85 (rawRisk=243.5 / issueTotal=132) → niveau "medium".
      await expect(page.locator('.badge-level.medium')).toHaveText(/MEDIUM/);

      // Gouvernance RESPONSIBLE : 12/132 = 9,1 % > 5 % → alerte affichée.
      await expect(page.locator('body')).toContainText('Attention');
      await expect(page.locator('body')).toContainText('dépasse 5%');

      // Graphiques Chart.js : présence uniquement (canvas non inspectable, cf. spec 11).
      await expect(page.locator('#cc-categories-chart')).toBeVisible();
      await expect(page.locator('#cc-severities-chart')).toBeVisible();
    });

    await test.step('3. Synthèse portefeuille /clean-code/synthese', async () => {
      await page.locator('#bouton-synthese-portefeuille').click();
      await expect(page).toHaveURL(/\/clean-code\/synthese/);

      // Tag groupe fonctionnel (composant partagé, cf. mes-projets/statistiques).
      await expect(page.locator('.tag-groupe-fonctionnel')).toContainText('tetris-game');

      // Ligne du projet dans le tableau 15 colonnes. Le niveau de risque ici
      // est calculé sur `historique.violations` (pas `clean_code.issue_total`
      // comme sur /clean-code) — valeur non prédite précisément par ce test.
      // Le texte affiché est le score (nombre), le niveau n'est qu'une classe
      // CSS sur le badge (pas de texte "MEDIUM"/"CRITICAL" dans le DOM) : on
      // vérifie juste la présence de la ligne et d'un badge de risque valide.
      const row = page.locator('.synthese-clean-code tbody tr', { hasText: 'TetrisGame' });
      await expect(row).toHaveCount(1);
      await expect(row.locator('.badge-level.critical, .badge-level.high, .badge-level.medium, .badge-level.low'))
        .toHaveCount(1);
    });
  });
});
