<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service\CommandRebuildHistorique;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;
use App\Service\{ClientService, UrlBuilderService};

/**
 * [Description SonarAnalysisFetcher]
 */
class SonarAnalysisFetcherService
{
    private static $sonarUrl = "sonar.url";

    public function __construct(
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private readonly ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {}

    /**
     * [Description for extractVersion]
     * Extraction robuste de la version
     *
     * @param array $analysis
     *
     * @return string
     *
     * Created at: 17/03/2026 17:01:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractVersion(array $analysis): string
    {
        if (!empty($analysis['projectVersion'])) {
            return $analysis['projectVersion'];
        }

        if (!empty($analysis['events'])) {

            foreach ($analysis['events'] as $event) {

                if (($event['category'] ?? null) === 'VERSION') {
                    return $event['name'];
                }
            }
        }

        return 'unknown';
    }

    /**
     * [Description for fetchAnalyses]
     * Récupère toutes les analyses d'un projet Sonar
     *
     * @param string $project_key
     *
     * @return array
     *
     * Created at: 17/03/2026 17:00:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function fetchAnalyses(string $project_key): array
    {
        $analyses = [];
        $page = 1;
        $pageSize = 500;

        $this->logger->info('[SonarAnalysisFetcher] ℹ️ Début de collecte', [
            'project_key' => $project_key
        ]);

        do {
            $url = $this->urlBuilder->build(
                $this->params->get(self::$sonarUrl),
                '/api/project_analyses/search',
                [
                    'project' => $project_key,
                    'ps' => $pageSize,
                    'p' => $page
                ]
            );

            $this->logger->debug('[SonarAnalysisFetcher] 🛠️ Appel API SonarQube', [
                'url' => $url
            ]);

            $response = $this->client->httpSonarQube($url);

            if ($response['code'] !== 200) {
                $this->logger->error('[SonarAnalysisFetcher] ❌ Erreur API Sonar', [
                    'status' => $response['code'],
                    'url' => $url
                ]);
                break;
            }

            $data = $response['json']['analyses'] ?? [];

            foreach ($data as $analysis) {

                $analyses[] = [
                    'analysisKey' => $analysis['key'],
                    'date' => $analysis['date'],
                    'version' => $this->extractVersion($analysis)
                ];
            }

            $total = $response['json']['paging']['total'] ?? 0;

            $page++;

        } while (($page - 1) * $pageSize < $total);

        /**
         * Tri sécurisé (l’API Sonar ne garantit pas toujours l’ordre)
         */
        usort($analyses, fn($a, $b) =>
            strtotime($b['date']) <=> strtotime($a['date'])
        );

        $this->logger->info('[SonarAnalysisFetcher] 📊 Fin de collecte', [
            'analyses_total' => count($analyses)
        ]);

        return $analyses;

    }

    /**
     * [Description for getLatestAnalysisPerVersion]
     *
     * @param array $analyses
     *
     * @return array
     *
     * Created at: 17/03/2026 16:39:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function fetchLatestAnalysesPerVersion(string $project_key): array
    {
        $analyses = $this->fetchAnalyses($project_key);
        $versions = [];

        foreach ($analyses as $analysis) {
            $version = $analysis['version'] ?? 'unknown';
            $date = new \DateTime($analysis['date']);

            if (!isset($versions[$version])) {
                $versions[$version] = $analysis;
                continue;
            }

            $existingDate = new \DateTime($versions[$version]['date']);

            if ($date > $existingDate) {
                $versions[$version] = $analysis;
            }
        }

        return array_values($versions);
    }

    /**
     * [Description for detectVersionType]
     *
     * @param string $version
     *
     * @return string
     *
     * Created at: 17/03/2026 23:36:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function detectVersionType(string $version): string
    {
        $v = strtolower($version);

        if (str_contains($v, '-snapshot')) {
            return 'snapshot';
        }

        if (str_contains($v, '-release')) {
            return 'release';
        }

        // projets Maven / Gradle non release ou snapshot
        if (preg_match('/-(rc|alpha|beta|m\d*)/i', $v)) {
            return 'autre';
        }

        return 'release';
    }

    /**
     * [Description for computeVersionCounters]
     *
     * @param array $analyses
     *
     * @return array
     *
     * Created at: 17/03/2026 23:36:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeVersionCounters(array $analyses): array
    {
        usort($analyses, fn($a, $b) =>
            strtotime($a['date']) <=> strtotime($b['date'])
        );

        $release = 0;
        $snapshot = 0;
        $autre = 0;

        foreach ($analyses as &$analysis) {

            $type = $this->detectVersionType($analysis['version']);

            if ($type === 'release') {
                $release++;
            }

            if ($type === 'snapshot') {
                $snapshot++;
            }

            if ($type === 'autre') {
                $autre++;
            }

            $analysis['version_release'] = $release;
            $analysis['version_snapshot'] = $snapshot;
            $analysis['version_autre'] = $autre;
        }

        return $analyses;
    }

}
