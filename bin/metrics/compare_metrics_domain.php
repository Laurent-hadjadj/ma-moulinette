<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  https://www.gnu.org/licenses/agpl-3.0.html
*/

/**
 * Comparaison de deux snapshots SonarQube /api/metrics/search
 * --------------------------------------------------------
 * 🧩 Version : 1.0.0
 * 🗂️ class   : `compare_metrics_domain.php`
 * 🧑‍💻 Auteur : Laurent HADJADJ
 * 🗓️ Dernière mise à jour : 2026-10-11
 *
 * Liste les métriques présentes dans le fichier "nouveau" (--new) mais absentes
 * du fichier "ancien" (--old), en excluant :
 *   - les métriques `hidden: true` ;
 *   - les métriques dont la clé commence par `new_` (métriques "New Code") ;
 *   - les métriques dont la clé commence par `branch_` (métriques de branche).
 * Résultat regroupé par `domain`, trié par domaine puis par clé.
 *
 * Les deux fichiers attendus sont des réponses brutes de
 * `GET /api/metrics/search?ps=500` (voir bin/metrics/metrics_<version>.json),
 * pas le résultat de `app:sonar:compare-metrics` (qui diffère toute paire de
 * champs, sans ce filtrage ni ce regroupement).
 *
 * 🧩 Usage :
 *
 * php bin/metrics/compare_metrics_domain.php --old=bin/metrics/metrics_26.3.0.json --new=bin/metrics/metrics_26.9.0.json [--exclude-domain=Logger --exclude-domain=RCI ...]
 *
 * 💬 Options :
 *   --old             Chemin du fichier JSON de la version de référence (obligatoire)
 *   --new             Chemin du fichier JSON de la version à comparer (obligatoire)
 *   --exclude-domain  Domaine à exclure du résultat (répétable). Utile pour retirer les
 *                      métriques apportées par un plugin installé seulement sur le
 *                      serveur interrogé pour --new (cf. avertissement ci-dessous).
 *
 * ⚠️ Ne permet pas de distinguer automatiquement une métrique réellement nouvelle
 * dans SonarQube d'une métrique apportée par un plugin installé sur le serveur
 * interrogé pour --new mais pas pour --old (ex. sonar-rci, Dependency-Check,
 * track-logger-method) : les deux apparaissent de façon identique dans
 * /api/metrics/search. Vérifier le `domain` retourné (ou utiliser
 * --exclude-domain) avant de conclure à une évolution de la plateforme
 * SonarQube elle-même.
 */

$options = getopt("", ["old:", "new:", "exclude-domain:"]);
if (!isset($options['old']) || !isset($options['new'])) {
    //NOSONAR
    die("❌ Utilisation : php bin/metrics/compare_metrics_domain.php --old=bin/metrics/metrics_X.json --new=bin/metrics/metrics_Y.json [--exclude-domain=...]\n");
}

$excludedDomains = (array) ($options['exclude-domain'] ?? []);
if (!is_string($options['old']) || !is_string($options['new'])) {
    //NOSONAR
    die("❌ --old et --new doivent être fournis une seule fois chacun.\n");
}
$oldPath = $options['old'];
$newPath = $options['new'];

/**
 * [Description for loadMetrics]
 * Charge et valide un snapshot /api/metrics/search.
 *
 * @param string $path
 *
 * @return array<int, array<string, mixed>>
 */
function loadMetrics(string $path): array
{
    if (!is_file($path)) {
        //NOSONAR
        die("❌ Fichier introuvable : {$path}\n");
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded) || !isset($decoded['metrics']) || !is_array($decoded['metrics'])) {
        //NOSONAR
        die("❌ Format inattendu (clé 'metrics' absente) : {$path}\n");
    }

    return $decoded['metrics'];
}

$oldMetrics = loadMetrics($oldPath);
$newMetrics = loadMetrics($newPath);

$oldKeys = array_flip(array_column($oldMetrics, 'key'));

$nouvelles = [];
foreach ($newMetrics as $metric) {
    $key = (string) $metric['key'];

    if (isset($oldKeys[$key])) {
        continue; // déjà présente dans l'ancienne version
    }
    if (!empty($metric['hidden'])) {
        continue;
    }
    if (str_starts_with($key, 'new_') || str_starts_with($key, 'branch_')) {
        continue;
    }
    if (in_array($metric['domain'] ?? '', $excludedDomains, true)) {
        continue;
    }

    $nouvelles[] = $metric;
}

usort($nouvelles, fn ($a, $b) => [$a['domain'] ?? '', $a['key']] <=> [$b['domain'] ?? '', $b['key']]);

echo "Nouvelles métriques ({$oldPath} → {$newPath}) : " . count($nouvelles) . "\n\n";

$parDomaine = [];
foreach ($nouvelles as $metric) {
    $parDomaine[$metric['domain'] ?? '(sans domaine)'][] = $metric;
}

foreach ($parDomaine as $domaine => $metrics) {
    echo "=== {$domaine} (" . count($metrics) . ") ===\n";
    foreach ($metrics as $metric) {
        echo "- {$metric['key']} | {$metric['name']} | {$metric['description']}\n";
    }
    echo "\n";
}
