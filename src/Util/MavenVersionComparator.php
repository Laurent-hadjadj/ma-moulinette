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
 * MODIF 2026-05-12 : comparateur de versions
 * Maven (format `MAJOR.MINOR.PATCH[-qualifier]`).
 *
 * Utilise par DcScanRepository::findPreviousScanAnyVersion pour determiner
 * la "version précédente" d'un projet selon une logique semver, et non plus
 * selon l'ordre d'ingestion (scan_date). Auparavant, ingérer v4.2.0 puis v4.1.0
 * faisait pointer le previous_any de v4.2.0 vers v4.0.0 au lieu de v4.1.0,
 * parce que v4.1.0 n'existait pas encore en base au moment ou v4.2.0 a ete
 * indexe. Avec le tri semver, peu importe l'ordre d'ingestion.
 *
 * Règles supportées :
 *   - Strip prefix `v` ou `V` (`v3.5.0` traite comme `3.5.0`)
 *   - Comparaison numérique par tuple sur la partie pointée
 *   - Tie-breaker sur le qualifier (apres le premier `-`) :
 *       SNAPSHOT < ALPHA* < BETA* < RC* < (vide) = RELEASE = FINAL
 *   - Fallback strcmp si la partie numérique n'est pas parsable
 *     (versions exotiques de type build hash, dates `2024.06.01`, etc.)
 *
 * Parseur complet Maven (Aether/Resolver) : on couvre
 * le 95% des cas reels d'un parc Java metier sans dépendance externe.
 */
final class MavenVersionComparator
{
    /**
     * [Description for compare]
     * Compare 2 versions a la manière de strcmp / usort.
     * Retourne -1 si $a < $b, 0 si égale, 1 si $a > $b.
     *
     * @param string $a
     * @param string $b
     *
     * @return int
     *
     * Created at: 12/05/2026 14:46:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function compare(string $a, string $b): int
    {
        $parsedA = self::parse($a);
        $parsedB = self::parse($b);

        // Fallback strcmp si l'un des deux n'est pas parsable
        if ($parsedA === null || $parsedB === null) {
            return strcmp($a, $b) <=> 0;
        }

        // Comparer composants numériques (pad avec 0 si longueurs differentes)
        $maxLen = max(count($parsedA['parts']), count($parsedB['parts']));
        for ($i = 0; $i < $maxLen; $i++) {
            $av = $parsedA['parts'][$i] ?? 0;
            $bv = $parsedB['parts'][$i] ?? 0;
            if ($av !== $bv) {
                return $av <=> $bv;
            }
        }

        // Tie-breaker qualifier
        return self::qualifierRank($parsedA['qualifier'])
            <=> self::qualifierRank($parsedB['qualifier']);
    }

    /**
     * [Description for parse]
     * Parse une version Maven en {parts: int[], qualifier: string}.
     * Retourne null si la partie numérique n'est pas un format `digits(.digits)*`.
     *
     * @param string $version
     *
     * @return array{parts: array<int, int>, qualifier: string}|null
     *
     * Created at: 12/05/2026 14:47:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function parse(string $version): ?array
    {
        $v = trim($version);
        if ($v === '') {
            return null;
        }

        // Strip prefix 'v' ou 'V'
        if (preg_match('/^[vV]/', $v)) {
            $v = substr($v, 1);
        }

        // Sépare partie numérique et qualifier (premier '-')
        $dashPos = strpos($v, '-');
        $numPart   = $dashPos === false ? $v : substr($v, 0, $dashPos);
        $qualifier = $dashPos === false ? '' : strtoupper(substr($v, $dashPos + 1));

        // Verifier que numPart est composée uniquement de chiffres et de points
        if (!preg_match('/^\d+(\.\d+)*$/', $numPart)) {
            return null;
        }

        $parts = array_map('intval', explode('.', $numPart));
        return ['parts' => $parts, 'qualifier' => $qualifier];
    }

    /**
     * [Description for qualifierRank]
     * Retourne un rang en fonction de la nature de la version.
     *
     * @param string $q
     *
     * @return int
     *
     * Created at: 12/05/2026 14:47:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function qualifierRank(string $q): int
    {
        if ($q === '' || $q === 'RELEASE' || $q === 'FINAL') {
            return 4;
        }
        if (str_starts_with($q, 'RC')) {
            return 3;
        }
        if (str_starts_with($q, 'BETA')) {
            return 2;
        }
        if (str_starts_with($q, 'ALPHA')) {
            return 1;
        }
        if ($q === 'SNAPSHOT') {
            return 0;
        }
        // Qualifier inconnu : assume stable (rank max).
        return 4;
    }
}
