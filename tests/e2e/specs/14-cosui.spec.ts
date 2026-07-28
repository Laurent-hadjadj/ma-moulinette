import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedForCosui } from '../helpers/db';
import { buildProjetToken } from '../helpers/token';
import { USERS } from '../helpers/users';

/**
 * Spec 14 — Module COSUI (Comité de Suivi).
 *
 * Acteur : Nathan (ROLE_COLLECTE, aucun rôle transverse — même périmètre
 *   tetris-game que spec 06/11/13). `/projet/cosui` n'exige aucun rôle
 *   métier dédié, juste ROLE_UTILISATEUR (implicite) + appartenance au
 *   groupe fonctionnel du projet (logique dupliquée dans CosuiController,
 *   pas le trait partagé ProjetPerimetreGuard — dette technique documentée,
 *   sans impact ici).
 *
 * Comme Clean Code (spec 13), la navigation passe par un token rot13+base64
 * (mêmes helpers `buildProjetToken()`).
 *
 * Contrairement à Clean Code, le flux spec 06 (collecte + enregistrement)
 * ne suffit PAS à peupler des données COSUI exploitables :
 *   - la fixture SonarQube ne produit qu'une seule analyse → aucune ligne
 *     `historique` avec `initial=true` (pas de "version de référence") ;
 *   - le flux spec 06 ne déclenche jamais l'action "Répartition par
 *     module" → aucune ligne `repartition` avec `control <> 'initial'`.
 * Seed dédié (`seed-after-spec-14-cosui-tetris.sql`, via
 * `resetAndSeedForCosui()`) : 2 lignes historique (référence + courante,
 * notes/compteurs réels) + 1 ligne repartition `control='complet (100%)'`.
 *
 * Bug applicatif documenté (non corrigé, hors périmètre de ce spec) :
 * `HistoriqueRepository::selectHistoriqueProjetLast/Reference` sélectionne
 * `menace_potentielle_totale` sans alias `AS nombre_hotspot` — le compteur
 * Hotspot (#hotspot-01) affiche donc toujours 0 quelle que soit la donnée
 * en base. Non asserté ici en dehors de 0.
 */
const PROJECT_KEY = 'tetris:TetrisGame';

test.describe('14 — COSUI', () => {
  test.beforeAll(() => {
    resetAndSeedForCosui();
  });

  test.setTimeout(60_000);

  test('Nathan consulte le Comité de Suivi pour tetris', async ({ page }) => {
    await login(page, USERS.nathan);

    const token = buildProjetToken(PROJECT_KEY);
    await page.goto(`/projet/cosui?token=${token}`);
    await expect(page).toHaveURL(/\/projet\/cosui\?token=/);

    // Setup lu depuis repartition.setup, control='complet (100%)' → 100%,
    // donc pas de bandeau "répartition partielle".
    await expect(page.locator('#js-setup')).toHaveText('20260722120000');
    await expect(page.locator('#js-repartition-partielle')).toHaveCount(0);

    // Notes de la version courante (sqale='A', reliability='A', security='A').
    await expect(page.locator('#note-01')).toHaveText(/A/);
    await expect(page.locator('#note-02')).toHaveText(/A/);
    await expect(page.locator('#note-03')).toHaveText(/A/);

    // Compteurs bug/vulnerability/code_smell de la version courante.
    await expect(page.locator('#blocker-02')).toContainText('0'); // bug_blocker
    await expect(page.locator('#critical-02')).toContainText('1'); // bug_critical
    await expect(page.locator('#major-02')).toContainText('3'); // bug_major

    // Tableau répartition : backend → "Métier", frontend → "Présentation"
    // (cf. ProjetCosuiService::generateRender(), mapping module → bloc).
    await expect(page.locator('#metier-reliability-02')).toContainText('1'); // backend_bug_critical
    await expect(page.locator('#presentation-code_smell-05')).toContainText('4'); // frontend_code_smell_critical

    // Radar Chart.js : présence uniquement (canvas non inspectable, cf. specs 11/13).
    await expect(page.locator('#graphique-note')).toBeVisible();

    // Modale "projet de référence" : notes de la version initiale (plus mauvaises).
    await page.locator('#affiche-projet-reference').click();
    await expect(page.locator('#modal-projet-reference')).toBeVisible();
    await expect(page.locator('#initial-note-01')).toHaveText(/C/); // sqale_rating référence
    await expect(page.locator('#initial-note-02')).toHaveText(/D/); // reliability_rating référence

    await page.locator('#bouton-fermer-projet-reference').click();
    await expect(page.locator('#modal-projet-reference')).toBeHidden();
  });
});
