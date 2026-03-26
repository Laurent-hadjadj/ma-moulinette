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
use App\Service\CommandRebuildHistorique\BuildMapHistoryService;
use App\Exception\SonarApiException;

/**
 * [Description SonarMetricsFetcherService]
 */
class SonarMetricsFetcherService
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";

    public function __construct(
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private BuildMapHistoryService $buildMapHistoryService,
        private readonly ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {
        $this->buildMapHistoryService = $buildMapHistoryService;
    }

    /**
     * [Description for fetchMetrics]
     *
     * @param string $project_key
     *
     * @return array
     *
     * Created at: 17/03/2026 04:05:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function fetchMetrics(string $project_key, string $analysis_key): array
    {
        $version = (int) trim($this->params->get('sonar.version'));
        $url = $this->urlBuilder->build(
            $this->params->get(static::$sonarUrl),
            '/api/measures/component',
            [
                'component' => $project_key,
                'metricKeys' => $this->buildMapHistoryService->metricsKey($version),
                'analysisId' => $analysis_key
            ]
        );

        $this->logger->debug('[SonarMetricsFetcher] 🛠️ Appel API SonarQube',
        ['url' => $url]);

        $response = $this->client->httpSonarQube($url);

        if ($response['code'] !== 200) {
            $this->logger->error('[SonarMetricsFetcher] ❌ Erreur lors de l\'appel API SonarQube', [
                'status' => $response['code'],
                'url' => $url]);
                throw new SonarApiException(
                sprintf(
                        'Erreur API Sonar (HTTP %s) pour %s',
                        $response['code'] ?? 'unknown',
                        $url
                ),
                $response
                );
        }

        if (!isset($response['json']['components'])) {
            $this->logger->warning('[SonarMetricsFetcher] ⚠️ Aucune metrics trouvée pour ce     projet', [
                'project_key' => $project_key,
                'analysis_id' => $analysis_key
                ]
            );
        }
        if (empty($response['json']['component']['measures'])){
            $this->logger->info('[SonarMetricsFetcher] Analyse sans métriques',
                [
                    'project_key' => $project_key,
                    'analysis_id' => $analysis_key
                ]
            );
        }

        $measures = $response['json']['component']['measures'] ?? [];
        $result = [];

        foreach ($measures as $measure) {
            $result[$measure['metric']] = $measure['value'] ?? 0;
        }

        return $result;
    }
}
