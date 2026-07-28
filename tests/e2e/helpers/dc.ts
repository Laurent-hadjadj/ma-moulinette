import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';

// __dirname est disponible nativement en CommonJS (mode par défaut Playwright).
// On remonte tests/e2e/helpers -> tests/e2e -> tests -> projectRoot
const projectRoot = resolve(__dirname, '..', '..', '..');
const processQueueScript = resolve(projectRoot, 'bin', 'e2e', 'process-dc-queue.ps1');

/** Token attendu par DependencyCheckTokenSubscriber en APP_ENV=test (cf. .env.test). */
export const DC_INGEST_TOKEN = 'test-dc-ingest-token-32chars-aaaa';

/**
 * Traite la queue d'ingestion DependencyCheck (POST /api/secure/dependency-check/upload
 * ne fait qu'enqueuer — un worker séparé transforme la queue en dc_scan/dc_finding
 * exploitables par les pages Twig). Aucun cron ne tourne en e2e : ce helper invoque
 * le worker directement, une fois, après chaque upload.
 *
 * @throws Error si le script PowerShell échoue
 */
export function processDcQueue(): void {
  execFileSync(
    'powershell.exe',
    ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', processQueueScript],
    { stdio: ['ignore', 'inherit', 'inherit'] }
  );
}

/**
 * Rapport DependencyCheck minimal mais réaliste (1 dépendance, 1 CVE) pour
 * tetris:TetrisGame — mêmes clés que le format réel attendu par
 * DependencyCheckIngester (cf. tests/Unit/Service/DependencyCheck/DependencyCheckIngesterTest.php).
 */
export function buildTetrisDcReport(): Record<string, unknown> {
  return {
    reportSchema: '1.1',
    scanInfo: { engineVersion: '12.2.0' },
    projectInfo: {
      groupID: 'tetris',
      artifactID: 'TetrisGame',
      version: '1.1.0-RELEASE',
      reportDate: '2026-07-27T08:00:00Z',
    },
    dependencies: [
      {
        sha1: 'a'.repeat(40),
        fileName: 'amqp-client-5.9.0.jar',
        packages: [{ id: 'pkg:maven/com.rabbitmq/amqp-client@5.9.0' }],
        vulnerabilities: [
          {
            name: 'CVE-2023-46120',
            source: 'NVD',
            severity: 'HIGH',
            cvssv3: { baseScore: 7.5, baseSeverity: 'HIGH', attackVector: 'NETWORK' },
            cwes: ['CWE-400'],
            description: 'OOM dans amqp-client',
            references: [{ source: 'NVD', url: 'http://x', name: 'ADV' }],
          },
        ],
      },
    ],
  };
}
