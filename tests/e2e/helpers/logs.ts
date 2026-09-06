import { existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

// __dirname : tests/e2e/helpers -> tests/e2e -> tests -> projectRoot
const projectRoot = resolve(__dirname, '..', '..', '..');
const logsDir = resolve(projectRoot, 'var', 'log');

/**
 * Fichiers de logs synthétiques pour le spec 19 (Admin Logs).
 *
 * `AdminLogController`/`LogArchiveService::listLogs()` lisent directement
 * `var/log/` via `scandir()` — pas de table DB, donc pas de seed SQL possible.
 * Aucune "collecte" applicative ne produit ces fichiers de façon fiable en
 * e2e : le handler `main` de `when@test` (monolog.yaml) est un
 * `fingers_crossed` qui ne flushe que sur une vraie erreur Doctrine, et les
 * handlers `request`/`messenger`/`deprecation`/`application` n'existent
 * même pas sous `when@test`. Seed direct sur le filesystem, même logique que
 * les seeds SQL directs des specs 07/14/15/17 pour des états que le flux
 * applicatif normal ne peut pas produire.
 *
 * Noms choisis pour ne JAMAIS collisionner avec un vrai fichier que l'appli
 * pourrait produire (cf. config/packages/monolog.yaml) :
 *   - le handler "application" n'existe que sous when@dev (app-dev.log) et
 *     when@prod (app.log), jamais sous when@test → "app-test.log" est
 *     garanti synthétique.
 *   - "request.log"/"messenger.log"/"deprecations.log" sont TOUJOURS écrits
 *     sans suffixe d'environnement (chemin statique dans monolog.yaml, jamais
 *     `%kernel.environment%`) → tout nom "<prefix>-<env>.log" pour ces 3
 *     types est garanti synthétique.
 *   - "prod.log" (main/prod) : cette machine ne fait jamais tourner l'appli
 *     en APP_ENV=prod localement.
 */
export const E2E_LOG_FIXTURES: Record<string, string> = {
  'app-test.log': 'application/test — ligne synthétique spec 19\n',
  'request-dev.log': 'request/dev — ligne synthétique spec 19\n',
  'messenger-dev.log': 'messenger/dev — ligne synthétique spec 19\n',
  'deprecations-test.log': 'deprecation/test — ligne synthétique spec 19\n',
  'prod.log': 'main/prod — ligne synthétique spec 19\n',
};

export function seedAdminLogFiles(): void {
  mkdirSync(logsDir, { recursive: true });
  for (const [name, content] of Object.entries(E2E_LOG_FIXTURES)) {
    writeFileSync(resolve(logsDir, name), content, 'utf-8');
  }
}

export function cleanupAdminLogFiles(): void {
  for (const name of Object.keys(E2E_LOG_FIXTURES)) {
    const path = resolve(logsDir, name);
    if (existsSync(path)) {
      rmSync(path);
    }
  }
}
