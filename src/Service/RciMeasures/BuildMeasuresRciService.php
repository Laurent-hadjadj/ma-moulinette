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

namespace App\Service\RciMeasures;

use App\Exception\SonarApiException;
use App\Service\{ClientService, UrlBuilderService};
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * [Description BuildMeasuresRciService]
 * Calcule le RCI (Risk Criticality Index, plugin sonar-rci porté en PHP dans
 * BuildMapHistoryService pour la mesure globale) par module (frontend/backend/
 * autre), de façon autonome.
 *
 * Un seul appel SonarQube (api/measures/component_tree, qualifiers=FIL,UTS — fichiers source et test —, paginé),
 * avec les mêmes metricKeys de sévérité que la mesure globale (BuildMapHistoryService, filtrées au strict nécessaire :
 * component_tree n'accepte pas les métriques de type "distribution" comme ncloc_language_distribution) :
 * SonarQube calcule déjà ncloc et les compteurs de sévérité par fichier, il suffit de les sommer par module.
 * Pas d'appel à issues/search : la mesure ne dépend donc pas du plafond de pagination de
 * 10 000 anomalies par requête, seulement de celui, bien moins probable, sur le nombre de fichiers.
 */
class BuildMeasuresRciService
{
    /** Poids par sévérité
     * @var array<string, int>
     */
    private array $severityWeights = [
        'BLOCKER'  => 10,
        'CRITICAL' => 5,
        'MAJOR'    => 3,
        'MINOR'    => 1,
        'INFO'     => 0,
    ];

    /** Mesures SonarQube par sévérité — taxonomie historique (SonarQube 8/9)
     * @var array<string, string>
     */
    private const OLD_SEVERITY_METRICS = [
        'BLOCKER'  => 'blocker_violations',
        'CRITICAL' => 'critical_violations',
        'MAJOR'    => 'major_violations',
        'MINOR'    => 'minor_violations',
        'INFO'     => 'info_violations',
    ];

    /** Mesures SonarQube par sévérité — taxonomie Clean Code (SonarQube 2024+)
     * @var array<string, string>
     */
    private const NEW_SEVERITY_METRICS = [
        'BLOCKER'  => 'software_quality_blocker_issues',
        'CRITICAL' => 'software_quality_high_issues',
        'MAJOR'    => 'software_quality_medium_issues',
        'MINOR'    => 'software_quality_low_issues',
        'INFO'     => 'software_quality_info_issues',
    ];

    /** Définition des constantes */
    private static string $sonarUrl = 'sonar.url';
    private static string $erreurSonarInconnue = 'Erreur SonarQube inconnue.';
    private static string $beginRegEx = '/\b(?:';
    private static string $endRegEx = ')\b/i';

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for buildRegexModules]
     * Construit les 3 regex de classification par module, à partir des mêmes
     * paramètres (`module.frontend`/`module.backend`/`module.autre`) que
     * BatchCollecteRepartitionController::batchAnalyseAnomalie().
     *
     * @return array{0: string, 1: string, 2: string}
     *
     * Created at: 25/09/2026 12:40:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildRegexModules(): array
    {
        $frontendKeywords = array_map('strtolower', array_map('trim', explode(',', (string) $this->params->get('module.frontend'))));
        $backendKeywords = array_map('strtolower', array_map('trim', explode(',', (string) $this->params->get('module.backend'))));
        $autreKeywords = array_map('strtolower', array_map('trim', explode(',', (string) $this->params->get('module.autre'))));

        return [
            self::$beginRegEx . implode('|', array_map('preg_quote', $frontendKeywords)) . self::$endRegEx,
            self::$beginRegEx . implode('|', array_map('preg_quote', $backendKeywords)) . self::$endRegEx,
            self::$beginRegEx . implode('|', array_map('preg_quote', $autreKeywords)) . self::$endRegEx,
        ];
    }

    /**
     * [Description for classifieModule]
     * Classe un chemin de fichier dans frontend/backend/autre/inconnu selon
     * les 3 regex fournies.
     *
     * @param array{0: string, 1: string, 2: string} $regexModules
     *
     * Created at: 25/09/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function classifieModule(string $path, array $regexModules): string
    {
        [$regexFrontend, $regexBackend, $regexAutre] = $regexModules;

        if (preg_match($regexFrontend, $path)) {
            return 'frontend';
        }
        if (preg_match($regexBackend, $path)) {
            return 'backend';
        }
        if (preg_match($regexAutre, $path)) {
            return 'autre';
        }

        return 'inconnu';
    }

    /**
     * [Description for severityMetrics]
     * Choisit la taxonomie de sévérité (ancienne ou Clean Code 2024+) selon
     * la version SonarQube configurée, comme BuildMapHistoryService::metricsKey().
     *
     * @return array<string, string>
     *
     * Created at: 25/09/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function severityMetrics(int $version): array
    {
        return $version >= 2024 ? self::NEW_SEVERITY_METRICS : self::OLD_SEVERITY_METRICS;
    }

    /**
     * [Description for weightedWeights]
     * Pondère 5 compteurs de sévérité selon $severityWeights.
     *
     * @param int $blocker
     * @param int $critical
     * @param int $major
     * @param int $minor
     * @param int $info
     *
     * @return int
     *
     * Created at: 28/09/2026 13:50:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function weightedWeights(int $blocker, int $critical, int $major, int $minor, int $info): int
    {
        return $blocker * $this->severityWeights['BLOCKER']
            + $critical * $this->severityWeights['CRITICAL']
            + $major * $this->severityWeights['MAJOR']
            + $minor * $this->severityWeights['MINOR']
            + $info * $this->severityWeights['INFO'];
    }

    /**
     * [Description for calculateRci]
     * Calcule le RCI selon la formule validée du plugin sonar-rci
     *   RCI = 100 - (poids total pondéré / ncloc) * 100, borné à 0 minimum.
     * `null` (pas de valeur numérique) quand le ncloc est nul (module vide) :
     * la division n'a pas de sens.
     *
     * @param int   $poidsTotal  Total weighted issues.
     * @param int|null $ncloc    Total lines of code. Null si la mesure
     *                           ncloc est absente de la réponse SonarQube.
     * @return float|null RCI value, ou null si non calculable (ncloc absent
     *                     ou nul — un projet sans code n'a pas de RCI, pas un
     *                     RCI de 0).
     * Created at: 25/09/2026 13:09:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function calculateRci(?int $ncloc, int $poidsTotal): ?float
    {
        if ($ncloc === null || $ncloc <= 0) {
            return null;
        }

        return max(round(100 - ($poidsTotal / $ncloc) * 100, 2), 0.0);
    }

    /**
     * [Description for getRCIRating]
     *
     * @param float|null $rci
     *
     * @return string|null
     *
     * Created at: 24/09/2026 18:54:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getRCIRating(?float $rci): ?string
    {
        if ($rci === null) {
            return null;
        }

        $tauxDette = 100 - $rci;

        if ($tauxDette <= 5) { return 'A'; }
        if ($tauxDette <= 10) { return 'B'; }
        if ($tauxDette <= 20) { return 'C'; }
        if ($tauxDette <= 50) { return 'D'; }
        return 'E';
    }

    /**
     * [Description for calculateMeasuresByModule]
     * Récupère, pour chaque fichier du projet (qualifiers=FIL,UTS — fichiers source et test —, paginé), le
     * ncloc et les compteurs de sévérité déjà agrégés par SonarQube (mêmes
     * metricKeys de sévérité que la mesure globale, filtrées au strict
     * nécessaire pour component_tree), puis les classe par module.
     * Chaque fichier n'est compté qu'une seule fois.
     *
     * @param string $maven_key
     * @param int    $version
     *
     * @return array<string, mixed>
     *
     * Created at: 25/09/2026 12:40:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function calculateMeasuresByModule(string $maven_key, int $version): array
    {
        $vide = ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0];
        $nclocParModule = ['frontend' => 0, 'backend' => 0, 'autre' => 0, 'inconnu' => 0];
        $severitiesByModule = ['frontend' => $vide, 'backend' => $vide, 'autre' => $vide, 'inconnu' => $vide];

        // On charge les fonctions de traitements des regex et des mesures en fonction de la version.
        $regexModules = $this->buildRegexModules();
        $severityMetrics = $this->severityMetrics($version);
        /* component_tree n'accepte pas toutes les métriques de metricsKey()
         * (les métriques de type "distribution", comme ncloc_language_distribution,
         * ne sont interrogeables que via measures/component).
         * On ne demande donc que le strict nécessaire. */
        $metricKeys = implode(',', array_merge(['ncloc'], array_values($severityMetrics)));

        $page = 1;
        $pageSize = 500;
        /* L'API SonarQube refuse de paginer au-delà de 10 000 résultats
         * (p * ps <= 10000). Contrairement à issues/search, les fichiers n'ont
         * pas d'axe naturel (comme la sévérité) pour découper la requête en
         * plusieurs tranches : au-delà, les mesures par module restent une
         * sous-estimation, signalée explicitement ci-dessous plutôt que masquée.
         */
        $maxPages = 20;
        $total = 0;

        try {
            do {
                $url = $this->urlBuilder->build(
                    $this->params->get(self::$sonarUrl),
                        '/api/measures/component_tree',
                    [
                        'component' => $maven_key,
                        'metricKeys' => $metricKeys,
                        'qualifiers' => 'FIL,UTS',
                        'p' => $page,
                        'ps' => $pageSize,
                    ]
                );

                $this->logger->debug('[Batch RCI - violationParModule] 🛠️ Appel de component_tree', ['url' => $url, 'page' => $page]);
                $result = $this->client->httpSonarQube($url);

                if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
                    throw new SonarApiException(
                        sprintf('Erreur API Sonar (HTTP %s) pour %s', $result['code'], $url),
                        $result
                    );
                }

                $components = $result['json']['components'] ?? [];
                foreach ($components as $component) {
                    $path = str_replace($maven_key . ':', '', (string) ($component['path'] ?? $component['key'] ?? ''));
                    $module = $this->classifieModule($path, $regexModules);

                    $mesures = [];
                    foreach ($component['measures'] ?? [] as $measure) {
                        $mesures[(string) ($measure['metric'] ?? '')] = (int) ($measure['value'] ?? 0);
                    }

                    $nclocParModule[$module] += $mesures['ncloc'] ?? 0;

                    foreach ($severityMetrics as $severity => $metricKey) {
                        $severitiesByModule[$module][$severity] += $mesures[$metricKey] ?? 0;
                    }
                }

                $total = (int) ($result['json']['paging']['total'] ?? count($components));
                $page++;
            } while (($page - 1) * $pageSize < $total && $page <= $maxPages);

            if ($total > $maxPages * $pageSize) {
                $this->logger->warning('[Batch RCI - MesuresParModule] ⚠️ Mesures tronquées : plus de fichiers que la pagination ne peut en récupérer', [
                    'maven_key' => $maven_key,
                    'total_sonarqube' => $total,
                    'plafond_pagination' => $maxPages * $pageSize,
                ]);
            }
        } catch (SonarApiException $e) {
            $this->logger->error('[Batch RCI - MesuresParModule] ❌ Erreur SonarQube', [
                'exception' => $e->getMessage(),
                'response' => $e->getResponse(),
            ]);

            return [
                'code' => $e->getResponse()['code'],
                'erreur' => $e->getResponse()['erreur'] ?? self::$erreurSonarInconnue,
            ];
        }

        $this->logger->info('[Batch RCI - MesuresParModule] ℹ️ Mesures calculées par module', [
            'maven_key' => $maven_key,
            'ncloc' => $nclocParModule,
            'severities' => $severitiesByModule,
        ]);

        return [
            'code' => 200,
            'ncloc' => $nclocParModule,
            'severities' => $severitiesByModule,
        ];
    }

    /**
     * [Description for calculateGlobalMeasures]
     *
     * @param string $maven_key
     * @param int $version
     *
     * @return array<string, mixed>
     *
     * Created at: 28/09/2026 15:56:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function calculateGlobalMeasures(string $maven_key, int $version): array
    {
        $severityMetrics = $this->severityMetrics($version);
        $metric_keys = implode(',', array_merge(['ncloc'], array_values($severityMetrics)));
        $url = $this->urlBuilder->build(
            $this->params->get(self::$sonarUrl),
            '/api/measures/component', [
                'component' => $maven_key,
                'metricKeys' => $metric_keys
            ]
        );

        $this->logger->debug('[Batch RCI - GlobalIssues] 🛠️ Appel à SonarQube /measures/component', ['url' => $url]);

        try {
            /** On récupère les mesures depuis l'API */
            $response = $this->client->httpSonarQube($url);

            if (isset($response['code']) && in_array($response['code'],
                [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
                throw new SonarApiException(
                            sprintf('Erreur API Sonar (HTTP %s) pour %s', $response['code'], $url),
                            $response
                        );
            }
        } catch (SonarApiException $e) {
            // MODIF 2026-09-28 : la méthode laissait auparavant l'exception
            // remonter non gérée, contrairement à calculateMeasuresByModule()
            // (retour ['code'=>..., 'erreur'=>...] géré par l'appelant).
            $this->logger->error('[Batch RCI - GlobalMeasures] ❌ Erreur SonarQube', [
                'exception' => $e->getMessage(),
                'response' => $e->getResponse(),
            ]);

            return [
                'code' => $e->getResponse()['code'],
                'erreur' => $e->getResponse()['erreur'] ?? self::$erreurSonarInconnue,
            ];
        }

        $measures = $response['json']['component']['measures'] ?? [];
        $global_measures = [];
        foreach ($measures as $measure) {
            $global_measures[(string) ($measure['metric'] ?? '')] = (int) ($measure['value'] ?? 0);
        }

        /* MODIF 2026-09-28 : compteurs par sévérité normalisés (BLOCKER/
         * CRITICAL/MAJOR/MINOR/INFO) à partir de la bonne taxonomie
         * ($severityMetrics, déjà choisie selon $version plus haut) — sans
         * ça l'appelant devait relire directement les clés brutes de
         * global_measures (ex. blocker_violations), toujours celles de
         * l'ancienne taxonomie même sur un serveur 2024+, où elles n'existent
         * pas : weight toujours à 0, RCI global toujours à 100. Même
         * normalisation que calculateMeasuresByModule(). */
        $severities = [];
        foreach ($severityMetrics as $severity => $metricKey) {
            $severities[$severity] = $global_measures[$metricKey] ?? 0;
        }

        $this->logger->info('[Batch RCI - GlobalMeasures] ℹ️ Mesures globales calculées', [
            'maven_key' => $maven_key,
            'global_measure' => $global_measures,
            'severities' => $severities,
        ]);

        return [
            'code' => 200,
            'global_measures' => $global_measures,
            'severities' => $severities,
        ];
    }
}
