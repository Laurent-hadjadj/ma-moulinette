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

use App\Service\ExtractName;

/**
 * [Description BuildMapHistoryService]
 */
class BuildMapHistoryService
{

    public function __construct(
        private ExtractName $extractName
    ) {
        $this->extractName = $extractName;
    }

    /**
     * METRICS
     * métriques que l'on souhaite historiser
     * pour éviter de faire des appels API inutiles et de stocker des données non pertinentes, on définit une liste de métriques à récupérer pour chaque analyse.
     *
     * @var array
     */

    //Quality Gate
    private const QUALITY_GATE_CORE = [
        'alert_status',
    ];

    //Size (taille du code)
    private const SIZE_CORE = [
        'lines',
        'ncloc',
        'ncloc_language_distribution',
        'files',
        'classes',
        'functions',
        'statements',
    ];

    //Comments
    private const COMMENTS_CORE = [
        'comment_lines',
        'comment_lines_density'
    ];

    //Complexity
    private const COMPLEXITY_CORE = [
        'complexity',
        'cognitive_complexity'
    ];

    //Coverage
    private const COVERAGE_CORE = [
        'coverage',
        'branch_coverage',
        'line_coverage',
        'lines_to_cover',
        'conditions_to_cover',
        'uncovered_conditions',
        'uncovered_lines'
    ];

    //Tests
    private const TESTS_CORE = [
        'tests',
        'test_execution_time',
        'test_errors',
        'test_failures',
        'skipped_tests',
        'test_success_density'
    ];

    //Duplications
    private const DUPLICATIONS_CORE = [
        'duplicated_blocks',
        'duplicated_files',
        'duplicated_lines',
        'duplicated_lines_density'
    ];

    //Issues (global)
    private const ISSUES_STATUS_CORE = [
        'open_issues',
        'reopened_issues',
        'confirmed_issues',
        'false_positive_issues'
    ];

    private const ISSUES_STATUS_CORE_10 = [
        'high_impact_accepted_issues',
        'accepted_issues'
    ];

    private const SOFTWARE_QUALITY_CORE = [
        'violations',
        'blocker_violations',
        'critical_violations',
        'major_violations',
        'minor_violations',
        'info_violations'
    ];

    private const SOFTWARE_QUALITY_2024 = [
        'software_quality_blocker_issues',
        'software_quality_high_issues',
        'software_quality_info_issues',
        'software_quality_low_issues',
        'software_quality_medium_issues'
    ];

    //Maintainability (SQALE)
    private const MAINTAINABILITY_CORE = [
        'code_smells',
        'sqale_index',
        'sqale_rating',
        'sqale_debt_ratio',
    ];

    private const MAINTAINABILITY_10 = [
        'maintainability_issues',
        'software_quality_maintainability_debt_ratio',
        'software_quality_maintainability_rating',
        'software_quality_maintainability_remediation_effort',
        'effort_to_reach_maintainability_rating_a'
    ];

    private const MAINTAINABILITY_2024 = [
        'software_quality_maintainability_issues',
        'effort_to_reach_software_quality_maintainability_rating_a'
    ];

    //Reliability
    private const RELIABILITY_CORE = [
        'bugs',
        'reliability_rating',
        'reliability_remediation_effort'
    ];

    private const RELIABILITY_10 = [
        'reliability_issues',
        'software_quality_reliability_remediation_effort',
        'software_quality_reliability_rating'
    ];

    private const RELIABILITY_2024 = [
        'software_quality_reliability_issues'
    ];

    //Security
    private const SECURITY_CORE = [
        'vulnerabilities',
        'security_rating',
        'security_remediation_effort'
    ];

    private const SECURITY_10 = [
        'security_issues',
        'software_quality_security_rating',
        'software_quality_security_remediation_effort'
    ];

    private const SECURITY_2024 = [
        'software_quality_security_issues',
    ];

    private const SECURITY_REVIEW_CORE = [
        'security_hotspots',
        'security_hotspots_reviewed',
        'security_review_rating',
    ];

    /**
     * [Description for fetchMetrics]
     *
     * @return array
     *
     * Created at: 17/03/2026 04:05:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function metricsKey(int $version): string
    {
        /**
         * ⚠️ Version des metrics en fonction de la version du serveur sonarqube.
         * La version _CORE correspond à la version 8️⃣ et 9️⃣ de SonarQube.
         * La version _10 correspond à la version LTA 🔟 du serveur SonarQube
         * La version _24 correspond aux versions LTA 2️⃣4️⃣, 2️⃣5️⃣ et 2️⃣6️⃣ du serveur SonarQube
         */

        $sonar_core = implode(',', array_unique(array_merge(
                self::QUALITY_GATE_CORE,
                self::SIZE_CORE,
                self::COMMENTS_CORE,
                self::COMPLEXITY_CORE,
                self::COVERAGE_CORE,
                self::TESTS_CORE,
                self::DUPLICATIONS_CORE,
                self::ISSUES_STATUS_CORE,
                self::SOFTWARE_QUALITY_CORE,
                self::MAINTAINABILITY_CORE,
                self::RELIABILITY_CORE,
                self::SECURITY_CORE,
                self::SECURITY_REVIEW_CORE
        )));

        $sonar_10 = implode(',', array_unique(array_merge(
                self::ISSUES_STATUS_CORE_10,
                self::MAINTAINABILITY_10,
                self::RELIABILITY_10,
                self::SECURITY_10,
        )));

        $sonar_2024 = implode(',', array_unique(array_merge(
                self::SOFTWARE_QUALITY_2024,
                self::MAINTAINABILITY_2024,
                self::RELIABILITY_2024,
                self::SECURITY_2024,
        )));

        // On récupère la version du serveur déclaré dans les properties.
        if ((int) $version == 10) {
            // Si la version est 10, on prend core + 10
            $query = $sonar_core . ',' . $sonar_10;
        } elseif ((int) $version == 2024) {
            // Si la version est 2024, on prend core + 10 + 2024
            $query = $sonar_core . ',' . $sonar_10 . ',' . $sonar_2024;
        } else {
            // Si la version est autre que 10 ou 2024, on ne met que le core
            $query = $sonar_core;
        }
        return $query;
    }

    /**
     * [Description for getCommentDensityRating]
     *
     * @param float $ratio
     *
     * @return string
     *
     * Created at: 25/03/2026 18:13:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getCommentDensityRating(float $ratio): string {
        if ($ratio <= 5) { return 'E'; }
        if ($ratio >= 15 && $ratio < 25) { return 'A'; }
        if ($ratio >= 25 && $ratio < 30) { return 'B'; }
        if ($ratio >= 30 && $ratio < 35) { return 'C'; }
        if ($ratio >= 35 && $ratio < 40) { return 'D'; }
        if ($ratio >= 40) { return 'E'; }
        return '--';
    }

    /**
     * [Description for getComplexityRating]
     *
     * @param float $ratio
     *
     * @return string
     *
     * Created at: 26/03/2026 18:25:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getComplexityRating(float $ratio): string {
		if ($ratio <= 3) {
				return 'A';
		} elseif ($ratio > 3 && $ratio <= 7) {
				return 'B';
		} elseif ($ratio > 7 && $ratio <= 12) {
				return 'C';
		} elseif ($ratio > 12 && $ratio <= 18) {
				return 'D';
		} elseif ($ratio > 18) {
				return 'E';
		}
		return '--';
    }

    /**
     * [Description for ratingToLetter]
     *
     * @param mixed $value
     *
     * @return [type]
     *
     * Created at: 21/03/2026 14:57:26 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function ratingToLetter(string $value) {
        return match((int)$value) {
            1 => 'A',
            2 => 'B',
            3 => 'C',
            4 => 'D',
            5 => 'E',
            default => '--'
        };
    }

    /**
     * [Description for generateKeys]
     * Générateur générique
     *
     * @param array $prefixes
     * @param array $suffixes
     *
     * @return array
     *
     * Created at: 25/03/2026 18:14:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function generateKeys(array $prefixes, array $suffixes): array {
        $keys = [];
        foreach ($prefixes as $p) {
            foreach ($suffixes as $s) {
                $keys["{$p}_{$s}"] = null;
            }
        }
        return $keys;
    }

    /**
     * [Description for metricsRebuild]
     *
     * @param array $measures
     *
     * @return array
     *
     * Created at: 19/03/2026 19:25:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function metricsRebuild(array $measures, array $analysis, string $project_key): array
    {

        $information_collecte = [
            'mode_collecte' => 'REBUILD',
            'utilisateur_collecte' => 'cli',
            'date_enregistrement' => new \DateTime()
        ];

        $information_projet = [
            'maven_key' => $project_key,
            'analyse_key' => $analysis['analysisKey'],
            'version' => $analysis['version'],
            'date_version' => new \DateTime($analysis['date']),
            'nom_projet' => $this->extractName->extractNameFromMavenKey($project_key),
        ];

        $custom_metrics = [
            'version_release' => isset($analysis['version_release']) ? (int) $analysis['version_release'] : null,
            'version_snapshot' => isset($analysis['version_snapshot']) ? (int) $analysis['version_snapshot'] : null,
            'version_autre' => isset($analysis['version_autre']) ? (int) $analysis['version_autre'] : null,
        ] + array_fill_keys([
            'suppress_warning','no_sonar','todo',
            'logger_info','logger_warn','logger_error','logger_debug',
            'repartition_frontend','repartition_backend','repartition_autre','repartition_inconnu',
        ], null);

        $custom_metrics += self::generateKeys(
            ['bug','vulnerability','code_smell'],
            ['blocker','critical','major','minor','info']
        );

        $custom_metrics += ['menace_potentielle_totale' => null];

        $custom_metrics += $this->generateKeys(
            ['menace_potentielle_to_review','menace_potentielle_reviewed'],
            ['high','medium','low']
        );

        $size_core = [
            'lines' => isset($measures['lines']) ? (int) $measures['lines'] : null,
            'ncloc' => isset($measures['ncloc']) ? (int) $measures['ncloc'] : null,
            'ncloc_language_distribution' => isset($measures['ncloc_language_distribution']) ? $measures['ncloc_language_distribution'] : null,
            'files' => isset($measures['files']) ? (int) $measures['files'] : null,
            'classes' => isset($measures['classes']) ? (int) $measures['classes'] : null,
            'functions' => isset($measures['functions']) ? (int) $measures['functions'] : null,
            'statements' => isset($measures['statements']) ? (int) $measures['statements'] : null,
        ];

        $comments_core = [
            'comment_lines' => isset($measures['comment_lines']) ? (int) $measures['comment_lines'] : null,
            'comment_lines_density' => isset($measures['comment_lines_density']) ? (float) $measures['comment_lines_density'] : null,
            'comment_lines_rating' => self::getCommentDensityRating($measures['comment_lines_density'])
        ];

        $coverage_core = [
            'coverage' => isset($measures['coverage']) ? (float) $measures['coverage'] : null,
            'branch_coverage' => isset($measures['branch_coverage']) ? (float) $measures['branch_coverage'] : null,
            'line_coverage' => isset($measures['line_coverage']) ? (float) $measures['line_coverage'] : null,
            'lines_to_cover' => isset($measures['lines_to_cover']) ? (int) $measures['lines_to_cover'] : null,
            'conditions_to_cover' => isset($measures['conditions_to_cover']) ? (int) $measures['conditions_to_cover'] : null,
            'uncovered_conditions' => isset($measures['uncovered_conditions']) ? (int) $measures['uncovered_conditions'] : null,
        ];

        $tests_core = [
            'tests' => isset($measures['tests']) ? (int) $measures['tests'] : null,
            'test_execution_time' => isset($measures['test_execution_time']) ? (int) $measures['test_execution_time'] : null,
            'test_errors' => isset($measures['test_errors']) ? (int) $measures['test_errors'] : null,
            'test_failures' => isset($measures['test_failures']) ? (int) $measures['test_failures'] : null,
            'skipped_tests' => isset($measures['skipped_tests']) ? (int) $measures['skipped_tests'] : null,
            'test_success_density' => isset($measures['test_success_density']) ? (float) $measures['test_success_density'] : null,
        ];

        $duplication_core = [
            'duplicated_blocks' => isset($measures['duplicated_blocks']) ? (int) $measures['duplicated_blocks'] : null,
            'duplicated_files' => isset($measures['duplicated_files']) ? (int) $measures['duplicated_files'] : null,
            'duplicated_lines' => isset($measures['duplicated_lines']) ? (int) $measures['duplicated_lines'] : null,
            'duplicated_lines_density' => isset($measures['duplicated_lines_density']) ? (float) $measures['duplicated_lines_density'] : null,
        ];

        $issues_status_core = [
            'open_issues' => isset($measures['open_issues']) ? (int) $measures['open_issues'] : null,
            'reopened_issues' => isset($measures['reopened_issues']) ? (int) $measures['reopened_issues'] : null,
            'confirmed_issues' => isset($measures['confirmed_issues']) ? (int) $measures['confirmed_issues'] : null,
            'false_positive_issues' => isset($measures['false_positive_issues']) ? (int) $measures['false_positive_issues'] : null,
        ];

        $issues_status_10 = [
            'high_impact_accepted_issues' => isset($measures['high_impact_accepted_issues']) ? (int) $measures['high_impact_accepted_issues'] : null,
            'accepted_issues' => isset($measures['accepted_issues']) ? (int) $measures['accepted_issues'] : null,
        ];

        $issues_core = [
            'violations' => isset($measures['violations']) ? (int) $measures['violations'] : null,
            'blocker_violations' => isset($measures['blocker_violations']) ? (int) $measures['blocker_violations'] : null,
            'critical_violations' => isset($measures['critical_violations']) ? (int) $measures['critical_violations'] : null,
            'major_violations' => isset($measures['major_violations']) ? (int) $measures['major_violations'] : null,
            'minor_violations' => isset($measures['minor_violations']) ? (int) $measures['minor_violations'] : null,
            'info_violations' => isset($measures['info_violations']) ? (int) $measures['info_violations'] : null,
        ];

        $issues_2024 = [
            'software_quality_blocker_issues' => isset($measures['software_quality_blocker_issues']) ? (int) $measures['software_quality_blocker_issues'] : null,
            'software_quality_high_issues' => isset($measures['software_quality_high_issues']) ? (int) $measures['software_quality_high_issues'] : null,
            'software_quality_info_issues' => isset($measures['software_quality_info_issues']) ? (int) $measures['software_quality_info_issues'] : null,
            'software_quality_low_issues' => isset($measures['software_quality_low_issues']) ? (int) $measures['software_quality_low_issues'] : null,
            'software_quality_medium_issues' => isset($measures['software_quality_medium_issues']) ? (int) $measures['software_quality_medium_issues'] : null,
        ];

        // On calcul les ration de complexité
        $complexity = isset($measures['complexity']) ? (int) $measures['complexity'] : null;
        $cognitive_complexity = isset($measures['cognitive_complexity']) ? (int) $measures['cognitive_complexity'] : null;
        $ncloc = isset($measures['ncloc']) ? (int) $measures['ncloc'] : null;

        if ($complexity !== null && $complexity !== 0) {
            $complexity_ratio = round($ncloc / $complexity, 1);
        } else {
                $complexity_ratio = null;
        }

        if ($cognitive_complexity !== null && $cognitive_complexity !== 0) {
            $cognitive_complexity_ratio = round($ncloc / $cognitive_complexity, 1);
        } else {
                $cognitive_complexity_ratio = null;
        }

        $complexity_core = [
            'complexity' => $complexity,
            'cognitive_complexity' => $cognitive_complexity,
            'complexity_ratio' => $complexity_ratio,
            'cognitive_complexity_ratio' => $cognitive_complexity_ratio,
            'complexity_rating' => $this->getComplexityRating($complexity_ratio),
            'cognitive_complexity_rating' => $this->getComplexityRating($cognitive_complexity_ratio),
        ];

        $maintainability_core = [
            'code_smells' => isset($measures['code_smells']) ? (int) $measures['code_smells'] : null,
            'sqale_index' => isset($measures['sqale_index']) ? (int) $measures['sqale_index'] : null,
            'sqale_rating' => isset($measures['sqale_rating']) ? self::ratingToLetter($measures['sqale_rating']) : null,
            'sqale_debt_ratio' => isset($measures['sqale_debt_ratio']) ? (float) $measures['sqale_debt_ratio'] : null,
        ];

        $maintainability_10 = [
            'maintainability_issues' => isset($measures['maintainability_issues']) ? (string) $measures['maintainability_issues'] : null,
            'software_quality_maintainability_debt_ratio' => isset($measures['software_quality_maintainability_debt_ratio']) ? (float) $measures['software_quality_maintainability_debt_ratio'] : null,
            'effort_to_reach_maintainability_rating_a' => isset($measures['effort_to_reach_maintainability_rating_a']) ? self::ratingToLetter($measures['effort_to_reach_maintainability_rating_a']) : null,
            'software_quality_maintainability_remediation_effort' => isset($measures['software_quality_maintainability_remediation_effort']) ? (float) $measures['software_quality_maintainability_remediation_effort'] : null,
        ];

        $maintainability_2024 = [
            'software_quality_maintainability_issues' => isset($measures['software_quality_maintainability_issues']) ? (string) $measures['software_quality_maintainability_issues'] : null,
            'effort_to_reach_software_quality_maintainability_rating_a' => isset($measures['effort_to_reach_software_quality_maintainability_rating_a']) ? self::ratingToLetter($measures['effort_to_reach_software_quality_maintainability_rating_a']) : null,
        ];

        $reliability_core = [
            'bugs' => isset($measures['bugs']) ? (int) $measures['bugs'] : null,
            'reliability_rating' => isset($measures['reliability_rating']) ? self::ratingToLetter($measures['reliability_rating']) : null,
            'reliability_remediation_effort' => isset($measures['reliability_remediation_effort']) ? (int) $measures['reliability_remediation_effort'] : null,
        ];

        $reliability_10 = [
            'reliability_issues' => isset($measures['reliability_issues']) ? (string) $measures['reliability_issues'] : null,
            'software_quality_reliability_rating' => isset($measures['software_quality_reliability_rating']) ? self::ratingToLetter($measures['software_quality_reliability_rating']) : null,
            'software_quality_reliability_remediation_effort' => isset($measures['software_quality_reliability_remediation_effort']) ? (int) $measures['software_quality_reliability_remediation_effort'] : null,
        ];

        $reliability_2024 = [
            'software_quality_reliability_issues' => isset($measures['software_quality_reliability_issues']) ? (string) $measures['software_quality_reliability_issues'] : null,
        ];

        $security_core = [
            'vulnerabilities' => isset($measures['vulnerabilities']) ? (int) $measures['vulnerabilities'] : null,
            'security_rating' => isset($measures['security_rating']) ? self::ratingToLetter($measures['security_rating']) : null,
            'security_remediation_effort' => isset($measures['security_remediation_effort']) ? (int) $measures['security_remediation_effort'] : null,
        ];

        $security_10 = [
            'software_quality_security_rating' => isset($measures['software_quality_security_rating']) ? self::ratingToLetter($measures['software_quality_security_rating']) : null,
            'security_issues' => isset($measures['security_issues']) ? (int) $measures['security_issues'] : null,
            'software_quality_security_remediation_effort' => isset($measures['software_quality_security_remediation_effort']) ? (int) $measures['software_quality_security_remediation_effort'] : null,
        ];

        $security_2024 = [
            'software_quality_security_issues' => isset($measures['software_quality_security_issues']) ? (string) $measures['software_quality_security_issues'] : null,
        ];

        $security_review_core = [
            'security_hotspots' => isset($measures['security_hotspots']) ? (int) $measures['security_hotspots'] : null,
            'security_hotspots_reviewed' => isset($measures['security_hotspots_reviewed']) ? (float) $measures['security_hotspots_reviewed'] : null,
            'security_review_rating' => isset($measures['security_review_rating']) ? self::ratingToLetter($measures['security_review_rating']) : null,
        ];
        $quality_gate = [
            'alert_status' => isset($measures['alert_status']) ? $measures['alert_status'] : 'UNKNOWN',
        ];

        return array_merge(
            $quality_gate,
            $information_collecte,
            $information_projet,
            $custom_metrics,
            $size_core,
            $comments_core,
            $coverage_core,
            $tests_core,
            $duplication_core,
            $issues_status_core,
            $issues_status_10,
            $issues_core,
            $issues_2024,
            $complexity_core,
            $maintainability_core,
            $maintainability_10,
            $maintainability_2024,
            $reliability_core,
            $reliability_10,
            $reliability_2024,
            $security_core,
            $security_10,
            $security_2024,
            $security_review_core,
        );
    }
}
