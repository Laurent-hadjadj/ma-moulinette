<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

declare(strict_types=1);

namespace App\Service;

/**
 * [Description LanguageDistributionService]
 * Parse le champ SonarQube `ncloc_language_distribution` et expose les libelles
 * lisibles pour les codes langages.
 *
 * Distribution langage de la page Suivi. Reprend la logique
 * `parseLanguageDistribution` qui existait privée dans ApiPeintureController.
 */
final class LanguageDistributionService
{
    /**
     * Mapping codes SonarQube -> libelle lisible. Codes inconnus retournes tels quels.
     */
    private const LABELS = [
        'java' => 'Java',
        'js'   => 'JavaScript',
        'ts'   => 'TypeScript',
        'py'   => 'Python',
        'php'  => 'PHP',
        'web'  => 'HTML',
        'css'  => 'CSS',
        'xml'  => 'XML',
        'json' => 'JSON',
        'yaml' => 'YAML',
        'sql'  => 'SQL',
        'sh'   => 'Shell',
        'cs'   => 'C#',
        'cpp'  => 'C++',
        'c'    => 'C',
        'go'   => 'Go',
        'rb'   => 'Ruby',
        'rust' => 'Rust',
        'kotlin' => 'Kotlin',
        'scala'  => 'Scala',
        'swift'  => 'Swift',
        'dart'   => 'Dart',
        'vbnet'  => 'VB.NET',
    ];

    /**
     * [Description for parse]
     * Parse le champ ncloc_language_distribution de SonarQube.
     *
     *  - Format texte 2026 :  "java=12345;ruby=56;xml=4"
     *  - Ancien JSON       :  {"java":12345,"ruby":56}
     *  - null / vide       :  []
     *
     * Separateurs paires : ; ou ,. Separateurs cle/valeur : = ou :.
     *
     * @return array<string, int>
     *
     * Created at: 14/07/2026 20:30:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function parse(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        // Format JSON (ancien)
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_map('intval', $decoded);
        }

        // Format texte
        $pairs = preg_split('/[;,]/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($pairs as $pair) {
            if (preg_match('/^\s*([^=:]+)\s*[=:]\s*(\d+)\s*$/', $pair, $m)) {
                $result[trim($m[1])] = (int) $m[2];
            }
        }
        return $result;
    }

    /**
     * [Description for getLabel]
     * Retourne le libelle lisible d'un code langage (ex: 'java' -> 'Java').
     * Code inconnu : retourne le code tel quel avec premiere lettre capitale.
     *
     * @param string $code
     *
     * @return string
     *
     * Created at: 14/07/2026 20:30:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getLabel(string $code): string
    {
        $code = strtolower(trim($code));
        return self::LABELS[$code] ?? ucfirst($code);
    }

    /**
     * [Description for buildDistributionMatrix]
     * Construit la matrice complete pour le tableau et le graphique de distribution.
     *
     * @param array<int, array<string, mixed>> $suivi  Lignes de selectUnionHistoriqueProjet
     *
     * @return array{
     *     languages: array<string, string>,    // code -> libelle, trie par lignes desc sur dernière version
     *     rows: array<int, array<string, mixed>>,  // par version : version, ncloc, parsed[code => int]
     *     chart: array{labels: list<string>, datasets: list<array{label: string, data: list<int>, backgroundColor: string}>}
     * }
     *
     * Created at: 14/07/2026 20:30:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function buildDistributionMatrix(array $suivi): array
    {
        if ($suivi === []) {
            return ['languages' => [], 'rows' => [], 'chart' => ['labels' => [], 'datasets' => []]];
        }

        // Parse chaque version
        $rows = [];
        foreach ($suivi as $row) {
            $parsed = $this->parse($row['ncloc_language_distribution'] ?? null);
            $rows[] = [
                'version' => $row['version'] ?? '',
                'date' => $row['date'] ?? null,
                'ncloc' => $row['ncloc'] ?? null,
                'mode_collecte' => $row['mode_collecte'] ?? null,
                'initial' => $row['initial'] ?? 0,
                'parsed' => $parsed,
            ];
        }

        // Union des langages presents sur toutes les versions
        $allCodes = [];
        foreach ($rows as $r) {
            foreach (array_keys($r['parsed']) as $code) {
                $allCodes[$code] = true;
            }
        }
        $allCodes = array_keys($allCodes);

        // Tri : par valeur desc sur la dernière version, tiebreak alpha
        $lastRow = end($rows);
        $lastParsed = $lastRow['parsed'];
        usort($allCodes, function (string $a, string $b) use ($lastParsed): int {
            $va = $lastParsed[$a] ?? 0;
            $vb = $lastParsed[$b] ?? 0;
            if ($va !== $vb) {
                return $vb <=> $va;
            }
            return strcmp($a, $b);
        });

        // Mapping ordonne code -> libelle
        $languages = [];
        foreach ($allCodes as $code) {
            $languages[$code] = $this->getLabel($code);
        }

        /* MODIF 2026-05-06 : on n'inclut dans le
         * graphique que les versions avec distribution non vide. SonarQube
         * search_history ne renvoie pas ncloc_language_distribution donc les
         * versions REBUILD ont parsed=[] et seraient des barres invisibles qui
         * décalent l'axe X. Le tableau garde toutes les versions (pour le contexte). */
        $chartRows = array_values(array_filter(
            $rows,
            static fn (array $r): bool => $r['parsed'] !== []
        ));

        // Datasets pour le bar chart empile (versions avec donnees seulement)
        $palette = $this->palette();
        $datasets = [];
        $i = 0;
        foreach ($allCodes as $code) {
            $data = [];
            foreach ($chartRows as $r) {
                $data[] = $r['parsed'][$code] ?? 0;
            }
            $datasets[] = [
                'label' => $languages[$code],
                'data' => $data,
                'backgroundColor' => $palette[$i % count($palette)],
            ];
            $i++;
        }

        $labels = array_map(static fn (array $r): string => (string) $r['version'], $chartRows);

        return [
            'languages' => $languages,
            'rows' => $rows,
            'chart' => ['labels' => $labels, 'datasets' => $datasets],
        ];
    }

    /**
     * [Description for palette]
     * Palette de couleurs pour le bar chart empile.
     *
     * @return list<string>
     *
     * Created at: 07/05/2026 08:17:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function palette(): array
    {
        return [
            '#00445b', '#3b89a3', '#187e3d', '#b3b300',
            '#e69500', '#971c09', '#c45d4e', '#7a4f9e',
            '#0a8a8a', '#b08a3e', '#5e3b71', '#246b51',
            '#0000CC', '#330000', '#333300', '#6600FF',
            '#660066', '#990033', '#CC9933', '#FFFF00',
            '#CC99FF', '#CC6699', '#246b51', '#000033',
            '#00445b', '#3b89a3', '#187e3d', '#b3b300',
            '#e69500', '#971c09', '#c45d4e', '#7a4f9e'
        ];
    }
}
