import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

// __dirname est dispo nativement en CommonJS (mode par defaut Playwright).
// On remonte tests/e2e/helpers -> tests/e2e -> tests -> projectRoot
const projectRoot = resolve(__dirname, '..', '..', '..');
const resetScript = resolve(projectRoot, 'bin', 'e2e', 'reset-e2e-data.ps1');
const seedScript = resolve(projectRoot, 'bin', 'e2e', 'seed-e2e.ps1');

// Base CIBLE des seeds/resets E2E. Override via env E2E_DB_NAME si besoin.
// Les scripts ps1 refusent ma_moulinette (PROD) sauf -AllowProd explicite.
const E2E_DB = process.env.E2E_DB_NAME || 'ma_moulinette_test';

function runPs(scriptPath: string, args: string[] = []): void {
  // Toujours injecter -DbName (defense in depth : le defaut ps1 est deja test,
  // mais on rend la cible explicite cote helper pour eviter toute surprise).
  const fullArgs = ['-DbName', E2E_DB, ...args];
  execFileSync(
    'powershell.exe',
    ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', scriptPath, ...fullArgs],
    { stdio: ['ignore', 'inherit', 'inherit'] }
  );
}

/**
 * Reset rapide de l'état E2E (équivalent du reset entre tests d'intégration
 * Symfony). À appeler dans `test.beforeAll()` des specs qui mutent la DB.
 *
 * Conserve : referentiels OWASP, versions ma_moulinette, admin, groupes défaut.
 * Wipe + reload : 5 users E2E, données projet, groupes custom.
 *
 * Avantages :
 *   - ~3s (vs ~30s du rebuild complet)
 *   - Pas de prompt password (utilise db_user/db_password)
 *   - Pas de sudo / postgres superuser
 *
 * @throws Error si le script PowerShell échoue
 */
export function resetE2EData(): void {
  runPs(resetScript, ['-Quiet']);
}

/**
 * Charge un fichier SQL de seed (relatif à `migrations/POSTGRESQL/`).
 * Utilisé pour réinjecter rapidement un état "post-spec X" sans rejouer
 * l'UI — équivalent `loadFixtures()` en intégration Symfony.
 */
export function seedSql(relativePath: string): void {
  runPs(seedScript, ['-File', relativePath, '-Quiet']);
}

/**
 * Reset + seed jusqu'à la fin de la spec 02 (5 groupes utilisateur en place).
 * Pour les specs 03+ qui ont besoin que les groupes existent.
 */
export function resetAndSeedAfterSpec02(): void {
  resetE2EData();
  seedSql('95_e2e\\seed-after-spec-02-groupes.sql');
}

/**
 * Reset + seed jusqu'à la fin de la spec 03 :
 *   - 5 groupes utilisateur (spec 02)
 *   - 4 users E2E activés avec leur rôle cible (spec 03)
 *   - Aurélie a `reset_password=true` (déclenche le flow nominal en spec 04)
 */
export function resetAndSeedAfterSpec03(): void {
  resetAndSeedAfterSpec02();
  seedSql('95_e2e\\seed-after-spec-03-users-actives.sql');
}

/**
 * Reset + seed jusqu'à la fin de la spec 04 :
 *   - état post-spec 03
 *   - Aurélie reset_password=false (flow reset déjà testé)
 *   - 1 projet tetris:TetrisGame en liste_projet avec tag "tetris-game"
 *     (nécessaire pour spec 05 qui sélectionne ce tag dans le formulaire
 *     groupe fonctionnel)
 */
export function resetAndSeedAfterSpec04(): void {
  resetAndSeedAfterSpec03();
  seedSql('95_e2e\\seed-after-spec-04-projet-tetris.sql');
}

/**
 * Reset + seed jusqu'à la fin de la spec 05 :
 *   - état post-spec 04
 *   - 1 groupe fonctionnel "tetris-game" créé
 *   - chaque user affecté à son groupe_utilisateur cible
 *   - chaque user a liste_groupe_fonctionnel = ["tetris-game"]
 *     (pour qu'ils puissent voir le projet tetris en /projet)
 */
export function resetAndSeedAfterSpec05(): void {
  resetAndSeedAfterSpec04();
  seedSql('95_e2e\\seed-after-spec-05-affectations.sql');
}

/**
 * Reset + seed pour le spec 08 (contrôle d'accès), indépendant du scénario
 * d'onboarding 01-07 :
 *   - état post-spec 03 (Josh/Nathan/Sophie/Aurélie actifs, rôles de base)
 *   - Nathan cumule en plus ROLE_SECURITY, ROLE_ACTIVITY, ROLE_BATCH,
 *     ROLE_ACTUATOR (rôles transverses qu'aucun user n'a nativement dans
 *     le scénario narratif) pour servir de cas positif
 */
export function resetAndSeedAfterSpec08(): void {
  resetAndSeedAfterSpec03();
  seedSql('95_e2e\\seed-after-spec-08-roles-transverses.sql');
}
