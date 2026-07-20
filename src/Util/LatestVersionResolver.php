<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Util;

/**
 * MODIF 2026-05-14 : classifieur + sélecteur
 * de version "release vs dev" pour les vues agrégées Dependency-Check.
 *
 * Pourquoi : un même artefact peut être scanné sous plusieurs versions
 * (1.0.0, 1.0.1, 1.0.1-SNAPSHOT, 2.0.0...). Compter les CVE sur toutes
 * les versions gonfle artificiellement les agrégats (10 versions partageant
 * les memes JARs = 10x les memes CVE). Les vues "pilotage" doivent ne
 * retenir qu'1 ligne par (group, artifact) :
 *   - mode "release" (RSSI/DSI) : dernière version stable (sans suffixe dev)
 *   - mode "overall" (lead dev) : dernière version ingérée tout court
 *
 * Definition d'une "release" :
 *   - Format `MAJOR.MINOR.PATCH[.X]` sans suffixe
 *   - OU avec suffixe whiteliste : `RELEASE`, `FINAL`, `GA`, `SP\d+`
 *   - Les pre-releases (`-SNAPSHOT`, `-RC*`, `-ALPHA*`, `-BETA*`, `-M\d+`...) sont rejetées
 *   - Les versions non parsables (build hashes, dates) sont rejetées
 *
 * S'appuie sur `MavenVersionComparator` pour la comparaison numérique.
 */
/**
 * [Description LatestVersionResolver]
 */
final class LatestVersionResolver
{
    /**
     * [Description for isRelease]
     * True si $version est une version "release" au sens défini ci-dessus.
     *
     * @param string $version
     *
     * @return bool
     *
     * Created at: 14/05/2026 09:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function isRelease(string $version): bool
    {
        $parsed = MavenVersionComparator::parse($version);
        if ($parsed === null) {
            return false;
        }

        $q = $parsed['qualifier'];

        // Qualifier vide -> version pure x.y.z -> release
        if ($q === '') {
            return true;
        }

        // Whitelist post-release Spring/Java standard
        if ($q === 'RELEASE' || $q === 'FINAL' || $q === 'GA') {
            return true;
        }

        // Service Pack : SP1, SP10, SP123...
        if (preg_match('/^SP\d+$/', $q) === 1) {
            return true;
        }

        // Build number numerique pur (ex: '1.2.3-1' -> qualifier '1')
        if (preg_match('/^\d+$/', $q) === 1) {
            return true;
        }

        return false;
    }

    /**
     * [Description for pickLatest]
     * Sélectionne la version maximale dans $versions selon le tri semver
     * `MavenVersionComparator`.
     *
     * @param array<int, string> $versions    Liste de versions candidates
     * @param bool               $releaseOnly Si true, filtre préalablement via isRelease()
     *
     * @return string|null La version max, ou null si liste vide / aucune candidate apres filtre
     *
     * Created at: 14/05/2026 09:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function pickLatest(array $versions, bool $releaseOnly): ?string
    {
        if ($versions === []) {
            return null;
        }

        $candidates = $releaseOnly
            ? array_values(array_filter($versions, [self::class, 'isRelease']))
            : array_values($versions);

        if ($candidates === []) {
            return null;
        }

        $max = $candidates[0];
        for ($i = 1, $n = count($candidates); $i < $n; $i++) {
            if (MavenVersionComparator::compare($candidates[$i], $max) > 0) {
                $max = $candidates[$i];
            }
        }

        return $max;
    }
}
