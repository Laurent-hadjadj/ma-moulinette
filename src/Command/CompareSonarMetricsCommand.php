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

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use App\Exception\FileNotFoundException;

/**
 * [Description CompareSonarMetricsCommand]
 */
#[AsCommand(
    name: 'app:sonar:compare-metrics',
    description: 'Compare les metrics SonarQube sur plusieurs versions.'
)]
class CompareSonarMetricsCommand extends Command
{

    protected function configure(): void
        {
            $this
                ->setName('app:sonar:compare-metrics-v1')
                ->setDescription('Compare les metrics entre deux versions de SonarQube.')
                ->addArgument('source', InputArgument::REQUIRED, 'Fichier JSON ou version source (ex: metrics_8.9.json).')
                ->addArgument('target', InputArgument::REQUIRED, 'Fichier JSON ou version cible (ex: metrics_9.8.json).')
                ->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'Répertoire contenant les fichiers JSON.', './bin/metrics/')
                ->addOption('ignore-id', null, InputOption::VALUE_NONE, 'Ignore la comparaison des IDs des metrics.')
                ->addOption('sonar-query-only', null, InputOption::VALUE_NONE, "Affiche uniquement la requête de l'API SonarQube.")
                ->addOption('sonar-project-key', null, InputOption::VALUE_OPTIONAL, "Ajoute la maven_key (ex. fr.ma-moulinette:monapplication) pour sonar-query-only.")
                ->addOption('summary-only', null, InputOption::VALUE_NONE, 'Affiche uniquement le résumé (sans détails)')
                ->addOption('min-impact', null, InputOption::VALUE_OPTIONAL, 'Impact minimum.', 0)
                ->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Format de sortie (cli|md|html|json).', 'cli');
        }

    /**
     * [Description for execute]
     * Dossier racine
     * console app:sonar:compare-metrics metrics_8.9.9.json metrics_9.9.8.json
     *
     * Dossier spécifique
     * app:sonar:compare-metrics metrics_8.9.9.json metrics_9.9.8.json --dir=./bin/metrics/
     *
     * Sans les ID
     * app:sonar:compare-metrics metrics_8.9.json metrics_9.8.json --ignore-id
     *
     * JSON (CI, API)
     * console app:sonar:compare-metrics metrics_8.9.9.json metrics_9.9.8.json --summary-only --format=json
     *
     * HTML (dashboard)
     * console app:sonar:compare-metrics metrics_8.9.9.json metrics_9.9.8.json --summary-only --format=html > report.html
     *
     * Markdown (PR)
     * console app:sonar:compare-metrics metrics_8.9.9.json metrics_9.9.8.json --summary-only --format=md
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 24/03/2026 11:21:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dir = rtrim($input->getOption('dir'), '/') . '/';
        $ignoreId = $input->getOption('ignore-id');
        $minImpact = (int)$input->getOption('min-impact');
        $sonarQueryOnly = $input->getOption('sonar-query-only');
        $sonarProjectKey = $input->getOption('sonar-project-key') ?: '<PROJECT_KEY>';

        $sourceFile = $this->resolveFiles($input->getArgument('source'), $dir);
        $targetFile = $this->resolveFiles($input->getArgument('target'), $dir);

        $io->title('Comparaison des metrics SonarQube');
        $this->printContext($io, $dir, $sourceFile, $targetFile);

        $v1 = $this->loadMetrics($sourceFile);
        $v2 = $this->loadMetrics($targetFile);

        $stats1 = $this->computeStats($v1);
        $stats2 = $this->computeStats($v2);

        $summaryOnly = $input->getOption('summary-only');

        $io->section('📊 Vue d’ensemble des métriques');

        $io->table(
            ['Version', 'Total', 'Public', 'Hidden'],
            [
                [$sourceFile, $stats1['total'], $stats1['public'], $stats1['hidden']],
                [$targetFile, $stats2['total'], $stats2['public'], $stats2['hidden']],
            ]
        );

        $deltaTotal = $stats2['total'] - $stats1['total'];
        $deltaPublic = $stats2['public'] - $stats1['public'];
        $deltaHidden = $stats2['hidden'] - $stats1['hidden'];

        $io->writeln(sprintf(
            "Δ Total: <info>%+d</info> | Δ Public: <info>%+d</info> | Δ Hidden: <info>%+d</info>",
            $deltaTotal,
            $deltaPublic,
            $deltaHidden
        ));


        $result = $this->computeDiff($v1, $v2, $ignoreId);

        $result['modified'] = $this->filterAndSort($result['modified'], $minImpact);

        if ($summaryOnly) {
            $format = (string) $input->getOption('format');

            $added = count($result['added']);
            $removed = count($result['removed']);
            $modified = count($result['modified']);
            $impactScore = $result['impactScore'];

            switch ($format) {
                case 'json':
                    $io->writeln($this->renderJsonSummary([
                        'source' => $sourceFile,
                        'target' => $targetFile,
                        'stats1' => $stats1,
                        'stats2' => $stats2,
                        'added' => $added,
                        'removed' => $removed,
                        'modified' => $modified,
                        'impactScore' => $impactScore
                    ]
                    ));
                    break;

                case 'html':
                    $io->writeln($this->renderHtmlSummary([
                        'source' => $sourceFile,
                        'target' => $targetFile,
                        'stats1' => $stats1,
                        'stats2' => $stats2,
                        'added' => $added,
                        'removed' => $removed,
                        'modified' => $modified,
                        'impactScore' => $impactScore
                    ]));
                    break;

                case 'md':
                    $io->writeln($this->renderStaticSummary([
                        'source' => $sourceFile,
                        'target' => $targetFile,
                        'stats1' => $stats1,
                        'stats2' => $stats2,
                        'added' => $added,
                        'removed' => $removed,
                        'modified' => $modified,
                        'impactScore' => $impactScore
                        ]
                    ));
                    break;

                default:
                    $io->error('Utilise --format=md|html|json avec --summary-only');
                    return Command::FAILURE;
                    break;
            }

            $io->success("Traitement terminée avec succès");
            return Command::SUCCESS;
        }

        if ($sonarQueryOnly) {
            $domains = $this->extractPublicMetricsByDomain($v2);
            $this->printApiQueries($io, $domains, $sonarProjectKey);
            $io->success("Traitement terminée avec succès");
            return Command::SUCCESS;
        }

        $this->printSummary($io, $result);
        $this->printAdded($io, $result['added'], $v2);
        $this->printRemoved($io, $result['removed']);
        $this->printModified($io, $result['modified']);
        $this->printLegacyMapping($result['removed'], $output);
        $this->printGlobalImpact($io, $result['impactScore']);

        // On sort la liste des metrics que l'on peut utiliser sur la nouvelle version.
        $domains = $this->extractPublicMetricsByDomain($v2);
        $this->printMetricsByDomain($io, $domains);

        $io->success("Impact global : {$result['impactScore']} (".$this->impactLevel($result['impactScore']).")");
        return Command::SUCCESS;
    }

    /**
     * [Description for resolveFiles]
     *
     * @param string $input
     * @param string $dir
     *
     * @return string
     *
     * Created at: 25/03/2026 14:34:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function resolveFiles(string $input, string $dir): string
    {
        // Si fichier direct
        if (file_exists($input)) {
            return $input;
        }

        // Sinon on considère que c'est une version
        $file = $dir . $input;

        if (!file_exists($file)) {
            throw new FileNotFoundException($file);
        }

        return $file;
    }

    /**
     * [Description for loadMetrics]
     *
     * @param string $file
     *
     * @return array
     *
     * Created at: 25/03/2026 14:34:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function loadMetrics(string $file): array
    {
        $data = json_decode(file_get_contents($file), true);

        $metrics = [];
        foreach ($data['metrics'] as $m) {
            $metrics[$m['key']] = $m;
        }

        return $metrics;
    }

    /**
     * [Description for computeStats]
     *
     * @param array $metrics
     *
     * @return array
     *
     * Created at: 25/03/2026 14:34:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function computeStats(array $metrics): array
    {
        $total = count($metrics);
        $public = 0;
        $hidden = 0;

        foreach ($metrics as $m) {
            if (($m['hidden'] ?? false) === true) {
                $hidden++;
            } else {
                $public++;
            }
        }

        return [
            'total' => $total,
            'public' => $public,
            'hidden' => $hidden
        ];
    }

    /**
     * [Description for formatValue]
     *
     * @param mixed $value
     *
     * @return string
     *
     * Created at: 25/03/2026 14:34:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) { return 'null'; }
        if (is_bool($value)) { return $value ? 'true' : 'false'; }
        return (string) $value;
    }

    /**
     * [Description for compareMetric]
     *
     * @param array $m1
     * @param array $m2
     * @param bool $ignoreId
     *
     * @return array
     *
     * Created at: 25/03/2026 14:35:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function compareMetric(array $m1, array $m2, bool $ignoreId): array
    {
        $diff = [];
        $fields = array_unique(array_merge(array_keys($m1), array_keys($m2)));

        foreach ($fields as $field) {
            if ($ignoreId && $field === 'id') {
                continue;
            }

            $v1 = $m1[$field] ?? null;
            $v2 = $m2[$field] ?? null;

            // Toujours comparer name et description
            if ($v1 !== $v2) {
                $diff[$field] = ['v1' => $v1, 'v2' => $v2];
            }
        }

        // Cas particulier si key identique mais name ou description changent
        if (($m1['key'] ?? null) === ($m2['key'] ?? null)) {
            if (($m1['name'] ?? '') !== ($m2['name'] ?? '')) {
                $diff['name'] = ['v1' => $m1['name'], 'v2' => $m2['name']];
            }
            if (($m1['description'] ?? '') !== ($m2['description'] ?? '')) {
                $diff['description'] = ['v1' => $m1['description'], 'v2' => $m2['description']];
            }
        }

        return $diff;
    }

    /**
     * [Description for computeImpact]
     *
     * @param string $metricKey
     * @param array $diff
     *
     * @return int
     *
     * Created at: 25/03/2026 14:35:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function computeImpact(string $metricKey, array $diff): int
    {
        $impact = 0;

        foreach ($diff as $field => $change) {

            if ($field === 'hidden') {
                // false → true = perte (plus grave)
                if ($change['v1'] === false && $change['v2'] === true) {
                    $impact += 8;
                }
                // true → false = gain (moins critique)
                elseif ($change['v1'] === true && $change['v2'] === false) {
                    $impact += 4;
                }
                continue;
            }

            $impact += match ($field) {
                'type' => 8,
                'domain' => 5,
                'key' => 10,
                'name', 'description' => 2,
                default => 0
            };
        }

        // Pondération métier (SQALE-like)
        $impact *= $this->metricWeight($metricKey);

        return $impact;
    }

    /**
     * [Description for impactLevel]
     *
     * @param int $score
     *
     * @return string
     *
     * Created at: 25/03/2026 14:35:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function impactLevel(int $score): string
    {
        return match (true) {
            $score < 10 => 'FAIBLE',
            $score < 30 => 'MODÉRÉ',
            $score < 60 => 'ÉLEVÉ',
            default => 'CRITIQUE'
        };
    }

    /**
     * [Description for metricWeight]
     *
     * @param string $key
     *
     * @return float
     *
     * Created at: 25/03/2026 14:35:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function metricWeight(string $key): float
    {
        return match (true) {

            // 🔴 Sécurité (très critique)
            str_contains($key, 'vulnerabilities'),
            str_contains($key, 'security') => 1.5,

            // 🟠 Fiabilité
            str_contains($key, 'bugs'),
            str_contains($key, 'reliability') => 1.3,

            // 🟡 Maintenabilité (SQALE)
            str_contains($key, 'code_smells'),
            str_contains($key, 'sqale') => 1.2,

            // 🔵 Couverture (moins critique)
            str_contains($key, 'coverage') => 0.8,

            default => 1.0
        };
    }

    /**
     * [Description for printLegacyMapping]
     *
     * @param array $removed
     * @param OutputInterface $output
     *
     * @return void
     *
     * Created at: 25/03/2026 14:35:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printLegacyMapping(array $removed, OutputInterface $output): void
    {
        $mapping = [
            'sqale_index' => [
                'replaced_by' => [
                    'new_technical_debt',
                    'development_cost',
                    'reliability_remediation_effort',
                    'security_remediation_effort'
                ],
                'comment' => 'Migration vers modèle multi-dimensionnel'
            ]
        ];

        foreach ($removed as $metric) {
            if (isset($mapping[$metric])) {
                $output->writeln("\n<question>$metric</question>");
                foreach ($mapping[$metric]['replaced_by'] as $new) {
                    $output->writeln("  → $new");
                }
                $output->writeln("  💡 {$mapping[$metric]['comment']}");
            }
        }
    }

    /**
     * [Description for computeDiff]
     *
     * @param array $v1
     * @param array $v2
     * @param bool $ignoreId
     *
     * @return array
     *
     * Created at: 25/03/2026 14:35:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function computeDiff(array $v1, array $v2, bool $ignoreId): array
    {
        $keysV1 = array_map('trim', array_keys($v1));
        $keysV2 = array_map('trim', array_keys($v2));

        $added = array_diff($keysV2, $keysV1);
        $removed = array_diff($keysV1, $keysV2);
        $common = array_intersect($keysV1, $keysV2);

        $modified = [];
        $impactScore = 0;

        foreach ($common as $key) {
            $metricV1 = $v1[$key];
            $metricV2 = $v2[$key];

            // Comparaison classique
            $diff = $this->compareMetric($metricV1, $metricV2, $ignoreId);

            // Forcer la détection pour les champs critiques
            foreach (['name','description','hidden'] as $field) {
                $v1Field = $metricV1[$field] ?? null;
                $v2Field = $metricV2[$field] ?? null;

                if ($v1Field !== $v2Field) {
                    $diff[$field] = ['v1' => $v1Field, 'v2' => $v2Field];
                }
            }

            if (!empty($diff)) {
                $impact = $this->computeImpact($key, $diff);
                $impactScore += $impact;

                $modified[$key] = [
                    'diff' => $diff,
                    'impact' => $impact
                ];
            }
        }

        // Impact des ajouts / suppressions
        $impactScore += count($added) * 2;
        $impactScore += count($removed) * 5;

        // Filtrage et tri : d'abord par impact décroissant, ensuite par clé alphabétique
        uasort($modified, function($a, $b) {
            if ($a['impact'] === $b['impact']) {
                return strcmp($a['diff']['name']['v1'] ?? '', $b['diff']['name']['v1'] ?? '');
            }
            return $b['impact'] <=> $a['impact'];
        });

        return compact('added', 'removed', 'modified', 'impactScore');
    }

    /**
     * [Description for filterAndSort]
     *
     * @param array $modified
     * @param int $minImpact
     *
     * @return array
     *
     * Created at: 25/03/2026 14:35:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function filterAndSort(array $modified, int $minImpact): array
    {
        // Filtrer les métriques dont l'impact est inférieur au minimum
        $filtered = array_filter($modified, fn($m) => $m['impact'] >= $minImpact);

        // Trier par impact décroissant, puis par clé alphabétique si égal
        uasort($filtered, function($a, $b) use ($filtered) {
            // Impact décroissant
            $impactCompare = $b['impact'] <=> $a['impact'];
            if ($impactCompare !== 0) {
                return $impactCompare;
            }

            // Récupérer les clés correspondantes
            $keyA = array_search($a, $filtered, true);
            $keyB = array_search($b, $filtered, true);

            return strcmp($keyA, $keyB);
        });

        return $filtered;
    }

    /**
     * [Description for printContext]
     *
     * @param SymfonyStyle $io
     * @param string $dir
     * @param string $src
     * @param string $tgt
     *
     * @return void
     *
     * Created at: 25/03/2026 14:35:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printContext(SymfonyStyle $io, string $dir, string $src, string $tgt): void
    {
        $io->definitionList(
            ['Dossier' => $dir],
            ['Source' => basename($src)],
            ['Cible' => basename($tgt)]
        );
    }

    /**
     * [Description for printSummary]
     *
     * @param SymfonyStyle $io
     * @param array $result
     *
     * @return void
     *
     * Created at: 25/03/2026 14:35:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printSummary(SymfonyStyle $io, array $result): void
    {
        $io->section('📊 Résumé');

        $io->definitionList(
            ['Ajouts' => count($result['added'])],
            ['Suppressions' => count($result['removed'])],
            ['Modifications' => count($result['modified'])],
            ['Score impact' => $result['impactScore']],
            ['Niveau' => $this->impactLevel($result['impactScore'])]
        );
    }

    /**
     * [Description for printAdded]
     *
     * @param SymfonyStyle $io
     * @param array $added
     * @param array $metricsV2
     *
     * @return void
     *
     * Created at: 25/03/2026 14:35:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printAdded(SymfonyStyle $io, array $added, array $metricsV2): void
    {
        if (empty($added)) { return; }

        $io->section('➕ Ajouts');

        foreach ($added as $key) {
            $label = $key;

            // Si le metric ajouté est hidden, on l’indique
            if (!empty($metricsV2[$key]['hidden'])) {
                $label .= ' <comment>(hidden)</comment>';
            }

            $io->writeln("• $label");
        }
    }

    /**
     * [Description for printRemoved]
     *
     * @param SymfonyStyle $io
     * @param array $removed
     *
     * @return void
     *
     * Created at: 25/03/2026 14:35:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printRemoved(SymfonyStyle $io, array $removed): void
    {
        if (empty($removed))  { return; }

        $io->section('❌ Suppressions');
        $io->listing(array_values($removed));
    }

    /**
     * [Description for printModified]
     *
     * @param SymfonyStyle $io
     * @param array $modified
     *
     * @return void
     *
     * Created at: 25/03/2026 14:35:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printModified(SymfonyStyle $io, array $modified): void
    {
        if (empty($modified)) {
            $io->success('Aucune modification significative.');
            return;
        }

        $io->section('🔄 Modifications');

        foreach ($modified as $key => $data) {

            $color = $this->colorImpact($data['impact']);

            $io->writeln(sprintf(
                "<%s>%s</> <comment>(impact: %d)</comment>",
                $color,
                $key,
                $data['impact']
            ));

            if ($key === 'hidden') {
                $io->writeln(
                    $vals['v2'] === true
                    ? "  ⚠️ métrique désormais cachée (API)"
                    : "  ✅ métrique désormais exposée (API)"
                );
            }

            foreach ($data['diff'] as $field => $vals) {
                $io->writeln(sprintf(
                    "  - <fg=yellow>%s</>: %s → %s",
                    $field,
                    $this->formatValue($vals['v1'] ?? null),
                    $this->formatValue($vals['v2'] ?? null)
                ));
            }

            $io->newLine();
        }
    }

    /**
     * [Description for colorImpact]
     *
     * @param int $impact
     *
     * @return string
     *
     * Created at: 25/03/2026 14:35:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function colorImpact(int $impact): string
    {
        return match (true) {
            $impact >= 5 => 'error',
            $impact >= 3 => 'comment',
            $impact >= 1 => 'info',
            default => 'fg=gray'
        };
    }

    /**
     * [Description for printGlobalImpact]
     *
     * @param SymfonyStyle $io
     * @param int $score
     *
     * @return void
     *
     * Created at: 25/03/2026 14:35:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printGlobalImpact(SymfonyStyle $io, int $score): void
    {
        $io->section('📊 Impact global');

        $io->text([
            "Score : $score",
            "Niveau : " . $this->impactLevel($score)
        ]);
    }

    /**
     * [Description for formatDelta]
     *
     * @param int $value
     *
     * @return string
     *
     * Created at: 25/03/2026 14:35:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function formatDelta(int $value): string
    {
        return $value > 0 ? "+$value" : (string)$value;
    }

    /**
     * [Description for renderStaticSummary]
     *
     * @param array $data
     *
     * @return string
     *
     * Created at: 25/03/2026 14:35:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function renderStaticSummary(array $data): string {

        $source = $data['source'];
        $target = $data['target'];
        $stats1 = $data['stats1'];
        $stats2 = $data['stats2'];
        $added = $data['added'];
        $removed = $data['removed'];
        $modified = $data['modified'];
        $impactScore = $data['impactScore'];

        $deltaTotal = $stats2['total'] - $stats1['total'];
        $deltaPublic = $stats2['public'] - $stats1['public'];
        $deltaHidden = $stats2['hidden'] - $stats1['hidden'];

        $level = $this->impactLevel($impactScore);

    return <<<MD
# 📊 SonarQube Metrics Comparison

## Versions
- Source : **{$source}**
- Cible  : **{$target}**

## 📦 Volumétrie

| Type    | {$source} | {$target} | Δ |
|---------|----------|----------|----|
| Total   | {$stats1['total']} | {$stats2['total']} | {$this->formatDelta($deltaTotal)} |
| Public  | {$stats1['public']} | {$stats2['public']} | {$this->formatDelta($deltaPublic)} |
| Hidden  | {$stats1['hidden']} | {$stats2['hidden']} | {$this->formatDelta($deltaHidden)} |

## 🔍 Changements

| Type          | Nombre |
|---------------|--------|
| ➕ Ajouts      | {$added} |
| ❌ Suppressions | {$removed} |
| 🔄 Modifications | {$modified} |

## 📈 Impact

- **Score** : {$impactScore}
- **Niveau** : **{$level}**

---
_Généré automatiquement par Ma-Moulinette_
MD;
    }

    /**
     * [Description for renderJsonSummary]
     *
     * @param array $data
     *
     * @return string
     *
     * Created at: 25/03/2026 14:35:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function renderJsonSummary(array $data): string
    {
        $source = $data['source'];
        $target = $data['target'];
        $stats1 = $data['stats1'];
        $stats2 = $data['stats2'];
        $added = $data['added'];
        $removed = $data['removed'];
        $modified = $data['modified'];
        $impactScore = $data['impactScore'];

        $data = [
            'source' => $source,
            'target' => $target,
            'metrics' => [
                'total' => [
                    'source' => $stats1['total'],
                    'target' => $stats2['total'],
                    'delta' => $stats2['total'] - $stats1['total']
                ],
                'public' => [
                    'source' => $stats1['public'],
                    'target' => $stats2['public'],
                    'delta' => $stats2['public'] - $stats1['public']
                ],
                'hidden' => [
                    'source' => $stats1['hidden'],
                    'target' => $stats2['hidden'],
                    'delta' => $stats2['hidden'] - $stats1['hidden']
                ]
            ],
            'changes' => [
                'added' => $added,
                'removed' => $removed,
                'modified' => $modified
            ],
            'impact' => [
                'score' => $impactScore,
                'level' => $this->impactLevel($impactScore)
            ],
            'generated_at' => date('c')
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * [Description for cssLevel]
     *
     * @param string $level
     *
     * @return string
     *
     * Created at: 25/03/2026 14:36:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function cssLevel(string $level): string
    {
        return match ($level) {
            'FAIBLE' => 'faible',
            'MODÉRÉ' => 'modere',
            'ÉLEVÉ' => 'eleve',
            'CRITIQUE' => 'critique',
            default => 'faible'
        };
    }

    /**
     * [Description for renderHtmlSummary]
     *
     * @param array $data
     *
     * @return string
     *
     * Created at: 25/03/2026 14:36:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function renderHtmlSummary(array $data): string {

        $source = $data['source'];
        $target = $data['target'];
        $stats1 = $data['stats1'];
        $stats2 = $data['stats2'];
        $added = $data['added'];
        $removed = $data['removed'];
        $modified = $data['modified'];
        $impactScore = $data['impactScore'];

        $level = $this->impactLevel($impactScore);

        $deltaTotal = $stats2['total'] - $stats1['total'];
        $deltaPublic = $stats2['public'] - $stats1['public'];
        $deltaHidden = $stats2['hidden'] - $stats1['hidden'];

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>SonarQube Metrics Comparison</title>
<style>
body { font-family: Arial; margin: 40px; background:#f9f9f9; }
h1 { color:#333; }
table { border-collapse: collapse; width: 60%; margin-bottom:20px; }
th, td { border:1px solid #ccc; padding:10px; text-align:center; }
th { background:#eee; }
.card { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); margin-bottom:20px;}
.badge { padding:5px 10px; border-radius:5px; color:white; }
.faible { background:green; }
.modere { background:orange; }
.eleve { background:red; }
.critique { background:black; }
</style>
</head>
<body>

<h1>📊 SonarQube Metrics Comparison</h1>

<div class="card">
<h2>Versions</h2>
<p><strong>Source:</strong> {$source} <br>
<strong>Cible:</strong> {$target}</p>
</div>

<div class="card">
<h2>📦 Volumétrie</h2>
<table>
<tr><th>Type</th><th>{$source}</th><th>{$target}</th><th>Δ</th></tr>
<tr><td>Total</td><td>{$stats1['total']}</td><td>{$stats2['total']}</td><td>{$deltaTotal}</td></tr>
<tr><td>Public</td><td>{$stats1['public']}</td><td>{$stats2['public']}</td><td>{$deltaPublic}</td></tr>
<tr><td>Hidden</td><td>{$stats1['hidden']}</td><td>{$stats2['hidden']}</td><td>{$deltaHidden}</td></tr>
</table>
</div>

<div class="card">
<h2>🔍 Changements</h2>
<ul>
<li>➕ Ajouts : {$added}</li>
<li>❌ Suppressions : {$removed}</li>
<li>🔄 Modifications : {$modified}</li>
</ul>
</div>

<div class="card">
<h2>📈 Impact</h2>
<p>Score : <strong>{$impactScore}</strong></p>
<p>Niveau : <span class="badge {$this->cssLevel($level)}">{$level}</span></p>
</div>

</body>
</html>
HTML;
    }

    /**
     * [Description for printApiQueries]
     *
     * @param SymfonyStyle $io
     * @param array $domains
     * @param mixed $sonarProjectKey
     *
     * @return void
     *
     * Created at: 25/03/2026 14:36:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printApiQueries(SymfonyStyle $io, array $domains, $sonarProjectKey): void
    {
        $io->section('🔌 Requêtes API SonarQube');

        foreach ($domains as $domain => $keys) {

            // ⚠️ sécurité anti-bug
            $keys = array_filter($keys, 'is_string');

            $metricsList = implode(',', $keys);

            $io->writeln("<info>$domain</info>");
            $io->writeln("/api/measures/component?component=$sonarProjectKey&metricKeys=$metricsList");
            $io->newLine();
        }
    }

    /**
     * [Description for printMetricsByDomain]
     *
     * @param SymfonyStyle $io
     * @param array $domains
     *
     * @return void
     *
     * Created at: 25/03/2026 14:36:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printMetricsByDomain(SymfonyStyle $io, array $domains): void
    {
        $io->section('📦 Metrics publiques par domaine');

        foreach ($domains as $domain => $keys) {

            $io->writeln("<info>$domain</info>");

            foreach ($keys as $key) {
                $io->writeln(" - $key");
            }

            $io->newLine();
        }
    }

    /**
     * [Description for extractPublicMetricsByDomain]
     *
     * @param array $metrics
     *
     * @return array
     *
     * Created at: 25/03/2026 14:36:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractPublicMetricsByDomain(array $metrics): array
    {
        $domains = [];

        foreach ($metrics as $metric) {

            // sécurité
            if (!is_array($metric) || !isset($metric['key'])) {
                continue;
            }

            // filtrage hidden
            if (($metric['hidden'] ?? false) === true) {
                continue;
            }

            $domain = $metric['domain'] ?? 'Other';
            // on pousse UNE STRING, pas le tableau
            $domains[$domain][] = $metric['key'];
        }

        // tri domaines
        ksort($domains);

        // tri clés
        foreach ($domains as &$keys) {
            $keys = array_unique($keys); // évite doublons
            sort($keys);
        }

        return $domains;
    }
}
