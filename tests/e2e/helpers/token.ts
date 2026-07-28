/**
 * Reproduit côté Node le token ROT13+base64 généré en JS pour naviguer
 * directement vers une page "signée" (Suivi, OWASP, Clean Code, Répartition,
 * COSUI) sans passer par le clic du bouton correspondant sur /projet.
 *
 * Algorithme exact (assets/js/mon-application/projet/index-projet.js,
 * ex. ligne ~1646 pour Clean Code) :
 *   1. salt = hash "sdbm" (hash*65599 + charCode) du maven_key, calculé en
 *      JS avec les opérateurs bitwise natifs (<<) — donc en arithmétique
 *      Int32 wrappée pour les décalages, mais somme finale non clampée.
 *   2. param = `${salt}|${maven_key}`
 *   3. base64 = btoa(param)
 *   4. token = rot13(base64)  (assets/js/common/encode.js)
 *
 * Reproduit ici en TypeScript avec les mêmes opérateurs JS (même moteur V8
 * que le navigateur testé) — donc bit-à-bit identique.
 */
function sdbmHash(mavenKey: string): number {
  let hash = 0;
  for (const char of mavenKey) {
    hash = char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
  }
  return hash;
}

function rot13(str: string): string {
  return str.replace(/[A-Za-z]/g, (char) => {
    const code = char.charCodeAt(0);
    const base = char >= 'a' ? 'a'.charCodeAt(0) : 'A'.charCodeAt(0);
    return String.fromCharCode(base + ((code - base + 13) % 26));
  });
}

/** Construit le token attendu par /clean-code, /owasp, /suivi/set, /repartition, /cosui. */
export function buildProjetToken(mavenKey: string): string {
  const salt = sdbmHash(mavenKey);
  const param = `${salt}|${mavenKey}`;
  const base64 = Buffer.from(param, 'utf8').toString('base64');
  return rot13(base64);
}
