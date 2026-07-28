import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

// __dirname est disponible nativement en CommonJS (mode par défaut Playwright).
// On remonte tests/e2e/helpers -> tests/e2e -> tests -> projectRoot
const projectRoot = resolve(__dirname, '..', '..', '..');
const refreshStatsScript = resolve(projectRoot, 'bin', 'e2e', 'refresh-admin-stats.ps1');

/**
 * Génère var/admin-stats.json (cloc + phpunit --list-tests) en invoquant
 * `app:admin:refresh-stats` — sans cela, /statistiques/dashboard affiche
 * uniquement des statistiques figées (repli codé en dur).
 *
 * @throws Error si le script PowerShell échoue
 */
export function refreshAdminStats(): void {
  execFileSync(
    'powershell.exe',
    ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', refreshStatsScript],
    { stdio: ['ignore', 'inherit', 'inherit'] }
  );
}
