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
use App\Exception\SonarApiException;

/**
 * [Description SonarMetricsFetcher]
 */
class SonarMetricsFetcherService
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";

    public function __construct(
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private readonly ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {}

    /**
     * METRICS
     * métriques que l'on souhaite historiser
     * pour éviter de faire des appels API inutiles et de stocker des données non pertinentes, on définit une liste de métriques à récupérer pour chaque analyse.
     *
     * @var array
     */
//Size (taille du code)
private const SIZE = [
    'lines',
    'ncloc',
    'ncloc_data',
    'ncloc_language_distribution',
    'files',
    'classes',
    'functions',
    'statements',
    'directories',
    'packages'
];

//Comments
private const COMMENTS = [
    'comment_lines',
    'comment_lines_density',
    'public_documented_api_density',
    'public_undocumented_api',
    'commented_out_code_lines'
];

//Complexity
private const COMPLEXITY = [
    'complexity',
    'cognitive_complexity',
    'file_complexity',
    'class_complexity',
    'function_complexity',
    'complexity_in_classes',
    'complexity_in_functions'
];

//Duplications
private const DUPLICATIONS = [
    'duplicated_blocks',
    'duplicated_files',
    'duplicated_lines',
    'duplicated_lines_density'
];

//Coverage
private const COVERAGE = [
    'coverage',
    'line_coverage',
    'branch_coverage',
    'lines_to_cover',
    'uncovered_lines',
    'conditions_to_cover'
];

private const OVERALL_COVERAGE = [
    'uncovered_conditions',
    'overall_coverage',
    'overall_line_coverage',
    'overall_branch_coverage'
];

//Tests
private const TESTS = [
    'tests',
    'test_errors',
    'test_failures',
    'test_execution_time',
    'skipped_tests',
    'test_success_density'
];

//Issues (global)
private const ISSUES = [
    'violations',
    'open_issues',
    'confirmed_issues',
    'reopened_issues',
    'false_positive_issues',
    'wont_fix_issues'
];

//Issues par type
private const TYPE = [
    'bugs',
    'vulnerabilities',
    'code_smells'
];

//Issues par sévérité (anciens)
private const SEVERITY = [
    'blocker_violations',
    'critical_violations',
    'major_violations',
    'minor_violations',
    'info_violations',
];

//Reliability
private const RELIABILITY = [
    'reliability_rating',
    'new_reliability_rating',
    'reliability_remediation_effort'
];

//Security
private const SECURITY = [
    'security_rating',
    'new_security_rating',
    'security_hotspots',
    'security_hotspots_reviewed',
    'security_review_rating',
    'security_remediation_effort'
];

//Maintainability (technical debt)
private const SQALE = [
    'sqale_index',
    'sqale_debt_ratio',
    'sqale_rating',
    'technical_debt',
];

//Quality Gate
private const QUALITY_GATE = [
    'alert_status',
    'quality_gate_details'
];

//Security hotspots
private const SECURITY_HOTSPOTS = [
    'security_hotspots',
    'new_security_hotspots',
    'security_hotspots_reviewed',
    'new_security_hotspots_reviewed'
];

//Portfolio / governance metrics (SonarQube récent)
private const SOFTWARE_QUALITY = [
    'software_quality_security_rating',
    'software_quality_reliability_rating',
    'software_quality_maintainability_rating',
    'software_quality_security_issues',
    'software_quality_reliability_issues',
    'software_quality_maintainability_issues'
];

//Language-specific metrics (selon plugins)
private const FUNCTIONS_COMPLEXITY_DISTRIBUTION = [
    'functions_complexity_distribution'
];

//Distribution metrics
private const DISTRIBUTION = [
    'ncloc_distribution',
    'complexity_distribution',
    'coverage_line_hits_data'
];

//SCM / repository metrics
private const SCM = [
    'lines_added',
    'lines_removed',
    'commit_count'
];

//Miscellaneous (uniquement si plugin SCM actif)
private const MISCELLANEOUS = [
    'generated_lines',
    'generated_ncloc',
    'projects',
    'development_cost'
];

private const METRICS = [
        'lines',
        'ncloc',
        'files',
        'classes',
        'functions',
        'coverage',
        'duplicated_lines_density',
        'sqale_debt_ratio',
        'tests',
        'violations',
        'bugs',
        'vulnerabilities',
        'code_smells',
        'blocker_violations',
        'critical_violations',
        'major_violations',
        'minor_violations',
        'info_violations',
        'reliability_rating',
        'security_rating',
        'sqale_rating',
        'hotspot_rating',
        'security_hotspots',
        'security_review_rating',
        'complexity',
        'cognitive_complexity',
        'function_complexity',
        'class_complexity',
        'file_complexity',
        'test_errors',
        'test_failures',
        'test_execution_time',
        'skipped_tests',
        'software_quality_security_rating', //sonarqube 10
        'software_quality_reliability_rating',
        'software_quality_maintainability_rating',
        'software_quality_security_issues',
        'software_quality_reliability_issues',
        'software_quality_maintainability_issues'
    ];

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
        $url = $this->urlBuilder->build(
            $this->params->get(static::$sonarUrl),
            '/api/measures/component',
            [
                'component' => $project_key,
                'metricKeys' => implode(',', self::METRICS),
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
