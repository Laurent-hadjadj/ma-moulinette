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

namespace App\Controller\Projet;

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use \Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{InformationProjet, Anomalie, AnomalieDetails, Mesures, NoSonar, Hotspots, Todo, Logger, LoggerDetail};
use App\Service\IsValideMavenKey;

/**
 * [Description ApiPeintureController]
 */
class ApiPeintureController extends AbstractController
{
    use AppUserAware;

    /**
     * [Description for __construct]
     *
     * Created at: 11/10/2023 13:38:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private IsValideMavenKey $isValideMavenKey,
        private LoggerInterface $logger,
    ) {}

    /** Définition des constantes */
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur404 = "Je n'ai pas trouvé les données. Vous devez lancer une collecte (Erreur 404).";
    private static string $loggerE400 = "[Collecte-Peinture] ❌ Requête mal formée : maven_key manquant ou invalide.";
    private static string $loggerE404 = "[Collecte-Peinture] ⚠️ Le projet n'a pas été trouvé ou la clé n'est pas correcte.";
    private static string $noData = 'Pas de données disponibles pour ce projet. Vous devez lancer une collecte.';

    /**
     * [Description for calculNoteHotspot]
     * retourne la note A, B, C, D ou E en fonction du ratio SonarQube.
     *
     * @param int $toReview
     * @param int $reviewed
     *
     * @return string
     *
     * Created at: 20/03/2024 19:34:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function calculNoteHotspot($toReview, $reviewed): string
    {
        $nbToReview = (int) $toReview;
        $nbReviewed = (int) $reviewed;
        $total = $nbToReview + $nbReviewed;
        if ($total === 0) {
            return 'A';
        }
        $ratio = ($nbReviewed / $total) * 100;
        if ($ratio >= 80) {
            return 'A';
        }
        if ($ratio >= 70) {
            return 'B';
        }
        if ($ratio >= 50) {
            return 'C';
        }
        if ($ratio >= 30) {
            return 'D';
        }
        return 'E';
    }

    /**
     * [Description for vulnerabilityProbability]
     *
     * @return array<int|string, mixed>
     *
     * Created at: 05/02/2025 08:09:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function vulnerabilityProbability(array $niveaux): array
    {
        $total = $high = $medium = $low = 0;

        foreach ($niveaux as $niveau) {
            if ($niveau['niveau'] == '1') {
                $high = $niveau['hotspot'];
            }
            if ($niveau['niveau'] == '2') {
                $medium = $niveau['hotspot'];
            }
            if ($niveau['niveau'] == '3') {
                $low = $niveau['hotspot'];
            }
        }
        $total = intval($high) + intval($medium) + intval($low);

        return [
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
            'total' => $total
        ];
    }


    /**
     * [Description for peintureProjetVersion]
     * Récupère les informations sur le projet : type de version, dernière version,
     * date de l'audit
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:53:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/version', name: 'peinture_projet_version', methods: ['POST'])]
    public function peintureProjetVersion(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/version");

        /** On instancie l'entityRepository */
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, [
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** Toutes les versions sonar par type (RELEASE, SNAPSHOT, AUTRE) */
        $map = ['maven_key' => $maven_key];
        $version = $informationProjetRepos->selectInformationProjetVersion($map);

        if ($version['code'] != 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectInformationProjetVersion.', [
                'code' => $version['code'],
                'erreur' => $version['erreur'] ?? self::$noData,
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => $version['code'],
                'type' => 'error',
                'message' => "La récupération des informations du projet a échouée  (Erreur {$version['code']}).",
                'trace' => $version['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $release =  $version['info']['version_release_sonar'] ?? 0;
        $snapshot = $version['info']['version_snapshot_sonar'] ?? 0;
        $autre = $version['info']['version_autre_sonar'] ?? 0;

        /** On prépare les données pour le graphique */
        $label = ['Release', 'Snapshot', 'Autre'];
        $dataset = [$release, $snapshot, $autre];

        return new JsonResponse(
            [
                'code' => 200,
                'release' => $release,
                'snapshot' => $snapshot,
                'autre' => $autre,
                'label' => $label,
                'dataset' => $dataset,
                'version' => $version['info']['version'] ?? 'N.C',
                'date' => $version['info']['date'] ?? '1971-07-04 00:00:00',
                'analyse_key' => $version['info']['analyse_key'] ?? 'N.C'
            ],
            Response::HTTP_OK
        );
    }

    /**
     * [Description for peintureProjetMesures]
     * Récupère les informations sur le projet : type de version, dernière version,
     * date de l'audit
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:53:58 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/mesures', name: 'peinture_projet_mesures', methods: ['POST'])]
    public function peintureProjetMesures(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/mesures");

        /** On instancie l'entityRepository */
        $mesuresRepos = $this->em->getRepository(Mesures::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, [
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /* MODIF 2026-05-04 :
         *   1. Renommage variable locale $request → $result (le paramètre $request était écrasé,
         *      anti-pattern déjà corrigé sur ApiSuiviController).
         *   2. Ajout d'un guard sur $result['mesures'] === false : fetchAssociative() renvoie false
         *      quand 0 ligne, ce qui causait "Trying to access array offset on false" plus bas.
         *   3. Type 'error' → 'error' (cohérence pattern). */
        /** On récupère la dernière version et sa date de publication */
        $map = ['maven_key' => $maven_key];
        $result = $mesuresRepos->selectMesuresVersionLast($map);
        if ($result['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectMesuresVersionLast.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
                'data' => $map
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'error',
                'message' => "❌ La récupération des mesures du projet a échoué (Erreur {$result['code']}).",
                'trace' => $result['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        if ($result['mesures'] === false || $result['mesures'] === null) {
            $this->logger->warning('[Collecte-Peinture] ⚠️ Aucune mesure trouvée pour ce projet.', [
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => "Aucune mesure n'a été collectée pour ce projet.",
                'trace' => null,
            ], Response::HTTP_OK);
        }

        /**
         * On récupère le tableau des languages de programmation.
         * SonarQube 2026 renvoie un string "java=12;ruby=3;xml=4" (ancien format
         * historique : un objet JSON). On parse les 2 formats pour robustesse.
         */
        $languageDistribution = $this->parseLanguageDistribution($result['mesures']['ncloc_language_distribution'] ?? null);

        /*
        * MODIF 2026-05-06 : Comptes NoSonar et To do par langage : la table mesures ne les stocke pas, il faut interroger les tables de detail (no_sonar, to do) qui ont 1 ligne
         * par occurrence. Les repos exposent deja un GROUP BY rule, on mappe ensuite
         * vers les noms de colonnes attendus par historique. Ces valeurs partent
         * via window.peintureData -> spread payload -> insert historique. */
        $noSonarRepos = $this->em->getRepository(NoSonar::class);
        $todoRepos = $this->em->getRepository(Todo::class);
        $noSonarRaw = $noSonarRepos->selectNoSonarRuleGroupByRule(['maven_key' => $maven_key]);
        $todoRaw = $todoRepos->selectTodoRuleGroupByRule(['maven_key' => $maven_key]);
        $noSonarCounts = self::aggregateNoSonarByRule($noSonarRaw['liste'] ?? []);
        $todoCounts = self::aggregateTodoByRule($todoRaw['liste'] ?? []);

        return new JsonResponse(
            [
                'code' => 200,
                'name' => $result['mesures']['project_name'],
                'project_name' => $result['mesures']['project_name'],
                'alert_status' => $result['mesures']['alert_status'] ?? null,
                'lines' => $result['mesures']['lines'] ?? null,
                'ncloc' => $result['mesures']['ncloc'] ?? null,
                'ncloc_language_distribution' => $languageDistribution,
                'files' => $result['mesures']['files'] ?? null,
                'classes' => $result['mesures']['classes'] ?? null,
                'functions' => $result['mesures']['functions'] ?? null,
                'statements' => $result['mesures']['statements'] ?? null,
                'comment_lines' => $result['mesures']['comment_lines'] ?? null,
                'comment_lines_density' => $result['mesures']['comment_lines_density'] ?? null,
                'comment_lines_rating' => $result['mesures']['comment_lines_rating'] ?? null,
                'coverage' => $result['mesures']['coverage'] ?? null,
                'branch_coverage' => $result['mesures']['branch_coverage'] ?? null,
                'line_coverage' => $result['mesures']['line_coverage'] ?? null,
                'lines_to_cover' => $result['mesures']['lines_to_cover'] ?? null,
                'conditions_to_cover' => $result['mesures']['conditions_to_cover'] ?? null,
                'uncovered_conditions' => $result['mesures']['uncovered_conditions'] ?? null,
                'coverage_rating' => $result['mesures']['coverage_rating'] ?? null,
                'tests' => $result['mesures']['tests'] ?? null,
                'test_execution_time' => $result['mesures']['test_execution_time'] ?? null,
                'test_errors' => $result['mesures']['test_errors'] ?? null,
                'test_failures' => $result['mesures']['test_failures'] ?? null,
                'skipped_tests' => $result['mesures']['skipped_tests'] ?? null,
                'test_success_density' => $result['mesures']['test_success_density'] ?? null,
                'duplicated_files' => $result['mesures']['duplicated_files'] ?? null,
                'duplicated_blocks' => $result['mesures']['duplicated_blocks'] ?? null,
                'duplicated_lines' => $result['mesures']['duplicated_lines'] ?? null,
                'duplicated_lines_density' => $result['mesures']['duplicated_lines_density'] ?? null,
                'duplicated_lines_rating' => $result['mesures']['duplicated_lines_rating'] ?? null,
                'complexity' => $result['mesures']['complexity'] ?? null,
                'complexity_rating' => $result['mesures']['complexity_rating'] ?? null,
                'cognitive_complexity' => $result['mesures']['cognitive_complexity'] ?? null,
                'cognitive_complexity_rating' => $result['mesures']['cognitive_complexity_rating'] ?? null,
                'complexity_ratio' => $result['mesures']['complexity_ratio'] ?? null,
                'cognitive_complexity_ratio' => $result['mesures']['cognitive_complexity_ratio'] ?? null,
                'open_issues' => $result['mesures']['open_issues'] ?? null,
                'reopened_issues' => $result['mesures']['reopened_issues'] ?? null,
                'confirmed_issues' => $result['mesures']['confirmed_issues'] ?? null,
                'false_positive_issues' => $result['mesures']['false_positive_issues'] ?? null,
                'accepted_issues' => $result['mesures']['accepted_issues'] ?? null,
                'high_impact_accepted_issues' => $result['mesures']['high_impact_accepted_issues'] ?? null,
                'violations' => $result['mesures']['violations'] ?? null,
                'blocker_violations' => $result['mesures']['blocker_violations'] ?? null,
                'critical_violations' => $result['mesures']['critical_violations'] ?? null,
                'major_violations' => $result['mesures']['major_violations'] ?? null,
                'minor_violations' => $result['mesures']['minor_violations'] ?? null,
                'info_violations' => $result['mesures']['info_violations'] ?? null,
                'software_quality_blocker_issues' => $result['mesures']['software_quality_blocker_issues'] ?? null,
                'software_quality_high_issues' => $result['mesures']['software_quality_high_issues'] ?? null,
                'software_quality_medium_issues' => $result['mesures']['software_quality_medium_issues'] ?? null,
                'software_quality_low_issues' => $result['mesures']['software_quality_low_issues'] ?? null,
                'software_quality_info_issues' => $result['mesures']['software_quality_info_issues'] ?? null,
                'code_smells' => $result['mesures']['code_smells'] ?? null,
                'maintainability_issues' => $result['mesures']['maintainability_issues'] ?? null,
                'sqale_index' => $result['mesures']['sqale_index'] ?? null,
                'sqale_debt_ratio' => $result['mesures']['sqale_debt_ratio'] ?? null,
                'sqale_rating' => $result['mesures']['sqale_rating'] ?? null,
                'effort_to_reach_maintainability_rating_a' => $result['mesures']['effort_to_reach_maintainability_rating_a'] ?? null,
                'software_quality_maintainability_issues' => $result['mesures']['software_quality_maintainability_issues'] ?? null,
                'software_quality_maintainability_rating' => $result['mesures']['software_quality_maintainability_rating'] ?? null,
                'software_quality_maintainability_debt_ratio' => $result['mesures']['software_quality_maintainability_debt_ratio'] ?? null,
                'software_quality_maintainability_remediation_effort' => $result['mesures']['software_quality_maintainability_remediation_effort'] ?? null,
                'effort_to_reach_software_quality_maintainability_rating_a' => $result['mesures']['effort_to_reach_software_quality_maintainability_rating_a'] ?? null,
                'bugs' => $result['mesures']['bugs'] ?? null,
                'reliability_issues' => $result['mesures']['reliability_issues'] ?? null,
                'reliability_rating' => $result['mesures']['reliability_rating'] ?? null,
                'reliability_remediation_effort' => $result['mesures']['reliability_remediation_effort'] ?? null,
                'software_quality_reliability_issues' => $result['mesures']['software_quality_reliability_issues'] ?? null,
                'software_quality_reliability_rating' => $result['mesures']['software_quality_reliability_rating'] ?? null,
                'software_quality_reliability_remediation_effort' => $result['mesures']['software_quality_reliability_remediation_effort'] ?? null,
                'vulnerabilities' => $result['mesures']['vulnerabilities'] ?? null,
                'security_issues' => $result['mesures']['security_issues'] ?? null,
                'security_rating' => $result['mesures']['security_rating'] ?? null,
                'security_remediation_effort' => $result['mesures']['security_remediation_effort'] ?? null,
                'software_quality_security_issues' => $result['mesures']['software_quality_security_issues'] ?? null,
                'software_quality_security_rating' => $result['mesures']['software_quality_security_rating'] ?? null,
                'software_quality_security_remediation_effort' => $result['mesures']['software_quality_security_remediation_effort'] ?? null,
                'security_hotspots' => $result['mesures']['security_hotspots'] ?? null,
                'security_review_rating' => $result['mesures']['security_review_rating'] ?? null,
                'security_hotspots_reviewed' => $result['mesures']['security_hotspots_reviewed'] ?? null,
                // MODIF 2026-05-06 : NoSonar par langage
                'java_no_sonar' => $noSonarCounts['java_no_sonar'],
                'python_no_sonar' => $noSonarCounts['python_no_sonar'],
                'php_no_sonar' => $noSonarCounts['php_no_sonar'],
                'suppress_warning' => $noSonarCounts['suppress_warning'],
                'no_pmd' => $noSonarCounts['no_pmd'],
                'check_style' => $noSonarCounts['check_style'],
                // MODIF 2026-05-06 : To do par langage
                'java_todo' => $todoCounts['java_todo'],
                'python_todo' => $todoCounts['python_todo'],
                'php_todo' => $todoCounts['php_todo'],
                'xml_todo' => $todoCounts['xml_todo'],
                'web_todo' => $todoCounts['web_todo'],
                'javascript_todo' => $todoCounts['javascript_todo'],
                'typescript_todo' => $todoCounts['typescript_todo'],
                'ruby_todo' => $todoCounts['ruby_todo'],
            ],
            Response::HTTP_OK
        );
    }

    /**
     * [Description for aggregateNoSonarByRule]
     * Mappe le résultat de selectNoSonarRuleGroupByRule (liste de [rule, total])
     * vers l'array attendu par window.peintureData -> historique.
     *
     * Rules supportées :
     *   - java:S1309   -> suppress_warning  (annotations @SuppressWarnings)
     *   - java:S1310   -> no_pmd            (annotations PMD-style)
     *   - java:S1315   -> check_style       (annotations Checkstyle)
     *   - java:NoSonar -> java_no_sonar     (commentaires // NOSONAR)
     *   - python:NoSonar -> python_no_sonar
     *   - php:NoSonar    -> php_no_sonar
     *
     * Toute valeur est int >= 0. Defaut 0 (pas null) car ce sont des compteurs.
     *
     * @param array<int, array{rule: string, total: int|string}> $rows
     * @return array{
     *   java_no_sonar: int, python_no_sonar: int, php_no_sonar: int,
     *   suppress_warning: int, no_pmd: int, check_style: int
     * }
     *
     * Created at: 12/07/2026 11:23:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function aggregateNoSonarByRule(array $rows): array
    {
        $counts = [
            'java_no_sonar' => 0,
            'python_no_sonar' => 0,
            'php_no_sonar' => 0,
            'suppress_warning' => 0,
            'no_pmd' => 0,
            'check_style' => 0,
        ];
        foreach ($rows as $row) {
            $rule = strtolower((string) ($row['rule']));
            $total = (int) ($row['total']);
            $key = match ($rule) {
                'java:s1309' => 'suppress_warning',
                'java:s1310' => 'no_pmd',
                'java:s1315' => 'check_style',
                'java:nosonar' => 'java_no_sonar',
                'python:nosonar' => 'python_no_sonar',
                'php:nosonar' => 'php_no_sonar',
                default => null,
            };
            if ($key !== null) {
                $counts[$key] += $total;
            }
        }
        return $counts;
    }

    /**
     * [Description for aggregateTodoByRule]
     * Mappe le résultat de selectTodoRuleGroupByRule (liste de [rule, total])
     * vers l'array attendu pour window.peintureData -> historique.
     *
     * Rules : *:S1135 par langage. Casse hétérogène cote Sonar (S1135 / s1135)
     * d'ou strtolower avant match.
     *
     * @param array<int, array{rule: string, total: int|string}> $rows
     * @return array{
     *   java_todo: int, python_todo: int, php_todo: int, xml_todo: int,
     *   web_todo: int, javascript_todo: int, typescript_todo: int, ruby_todo: int
     * }
     *
     * Created at: 12/07/2026 11:22:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function aggregateTodoByRule(array $rows): array
    {
        $counts = [
            'java_todo' => 0,
            'python_todo' => 0,
            'php_todo' => 0,
            'xml_todo' => 0,
            'web_todo' => 0,
            'javascript_todo' => 0,
            'typescript_todo' => 0,
            'ruby_todo' => 0,
        ];
        foreach ($rows as $row) {
            $rule = strtolower((string) ($row['rule']));
            $total = (int) ($row['total']);
            $key = match ($rule) {
                'java:s1135' => 'java_todo',
                'python:s1135' => 'python_todo',
                'php:s1135' => 'php_todo',
                'xml:s1135' => 'xml_todo',
                'web:s1135' => 'web_todo',
                'javascript:s1135' => 'javascript_todo',
                'typescript:s1135' => 'typescript_todo',
                'ruby:s1135' => 'ruby_todo',
                default => null,
            };
            if ($key !== null) {
                $counts[$key] += $total;
            }
        }
        return $counts;
    }

    /**
     * [Description for peintureProjetAnomalie]
     * Récupère les informations sur la dette technique et les anomalies
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:54:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/anomalie', name: 'peinture_projet_anomalie', methods: ['POST'])]
    public function peintureProjetAnomalie(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/anomalie");

        /** On instancie l'entityRepository */
        $anomalieRepos = $this->em->getRepository(Anomalie::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, [
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On récupère la dernière version et sa date de publication */
        $map = ['maven_key' => $maven_key];
        $anomalie = $anomalieRepos->selectAnomalie($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($anomalie['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectAnomalie.', [
                'code' => $anomalie['code'],
                'erreur' => $anomalie['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $anomalie['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectAnomalie (Erreur {$anomalie['code']}).",
                'trace' => $anomalie['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        /**
         * Dette : Dette total, répartition de la dette en fonction du type.
         * On récupère la valeur en jour, heure, minute pour l'affichage et la valeur en minutes
         * pour la historique (i.e on fera les comparaison sur cette valeur)
         */
        $dette = $anomalie['liste'][0]['dette'];
        $detteMinute = $anomalie['liste'][0]['dette_minute'];
        $detteReliability = $anomalie['liste'][0]['dette_reliability'];
        $detteReliabilityMinute = $anomalie['liste'][0]['dette_reliability_minute'];
        $detteVulnerability = $anomalie['liste'][0]['dette_vulnerability'];
        $detteVulnerabilityMinute = $anomalie['liste'][0]['dette_vulnerability_minute'];
        $detteCodeSmell = $anomalie['liste'][0]['dette_code_smell'];
        $detteCodeSmellMinute = $anomalie['liste'][0]['dette_code_smell_minute'];

        /** répartition par types tous les bugs, les vulnérabilités et les code smell */
        $typeBug = $anomalie['liste'][0]['bug'];
        $typeVulnerability = $anomalie['liste'][0]['vulnerability'];
        $typeCodeSmell = $anomalie['liste'][0]['code_smell'];

        /** Severity */
        $severityBlocker = $anomalie['liste'][0]['blocker'];
        $severityCritical = $anomalie['liste'][0]['critical'];
        $severityMajor = $anomalie['liste'][0]['major'];
        $severityInfo = $anomalie['liste'][0]['info'];
        $severityMinor = $anomalie['liste'][0]['minor'];

        /** Module */
        $frontend = $anomalie['liste'][0]['frontend'] ?? 0;
        $backend = $anomalie['liste'][0]['backend'] ?? 0;
        $autre = $anomalie['liste'][0]['autre'] ?? 0;
        $inconnu = $anomalie['liste'][0]['inconnu'] ?? 0;

        return new JsonResponse([
            'code' => 200,
            'dette' => $dette,
            'detteReliability' => $detteReliability,
            'detteVulnerability' => $detteVulnerability,
            'detteCodeSmell' => $detteCodeSmell,
            'detteMinute' => $detteMinute,
            'detteReliabilityMinute' => $detteReliabilityMinute,
            'detteVulnerabilityMinute' => $detteVulnerabilityMinute,
            'detteCodeSmellMinute' => $detteCodeSmellMinute,
            'bug_total' => $typeBug, //tous les bugs
            'vulnerability' => $typeVulnerability,
            'code_smell_total' => $typeCodeSmell, //tous les code smell
            'blocker' => $severityBlocker,
            'critical' => $severityCritical,
            'info' => $severityInfo,
            'major' => $severityMajor,
            'minor' => $severityMinor,
            'frontend' => $frontend,
            'backend' => $backend,
            'autre' => $autre,
            'inconnu' => $inconnu,
        ], Response::HTTP_OK);
    }

    /**
     * MODIF 2026-05-15 : endpoint drill-down loggers.
     * Retourne 4 datasets pour la page projet et sa modale :
     *   - breakdown_level     : nb par niveau (info/warn/error/debug)
     *   - breakdown_framework : nb par framework (SLF4J / Commons Logging / "—")
     *   - breakdown_matrix    : nb par (level, framework) — pour charts empilés
     *   - details             : liste complète des occurrences (max ~100, tri file_path + line)
     *
     * Pattern aligné sur peintureProjetAnomalie : validation maven_key,
     * isValideMavenKey, 4 queries repo, JsonResponse.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * Created at: 15/05/2026 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/logger-details', name: 'peinture_projet_logger_details', methods: ['POST'])]
    public function peintureProjetLoggerDetails(Request $request): Response
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/logger-details");

        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, ['payload' => $data ?? self::$noData]);
            return new JsonResponse([
                'code'    => 400,
                'type'    => 'error',
                'message' => self::$erreur400,
                'trace'   => null,
            ], Response::HTTP_OK);
        }

        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, ['maven_key' => $maven_key]);
            return new JsonResponse([
                'code'    => 404,
                'type'    => 'warning',
                'message' => self::$erreur404,
                'trace'   => null,
            ], Response::HTTP_OK);
        }

        $detailsRepos = $this->em->getRepository(LoggerDetail::class);

        $byLevel     = $detailsRepos->countByMavenKeyGroupedByLevel($maven_key);
        $byFramework = $detailsRepos->countByMavenKeyGroupedByFramework($maven_key);
        $byMatrix    = $detailsRepos->countByMavenKeyGroupedByLevelAndFramework($maven_key);
        $details     = $detailsRepos->selectByMavenKey($maven_key);
        // En cas d’échec d'une des 4 queries on log + on remonte des listes vides
        // (page projet doit rester affichable meme sans drill-down).
        /* MODIF 2026-05-20 : clé 'code' garantie par le type de retour de tous les repos. */
        foreach ([$byLevel, $byFramework, $byMatrix, $details] as $r) {
            if ($r['code'] !== 200) {
                $this->logger->error('[Collecte-Peinture] ❌ Échec lecture logger_details.', [
                    'maven_key' => $maven_key,
                    'code'      => $r['code'],
                    'erreur'    => $r['erreur']    ?? self::$noData,
                ]);
            }
        }

        return new JsonResponse([
            'code'                => 200,
            'maven_key'           => $maven_key,
            'breakdown_level'     => $byLevel['liste']     ?? [],
            'breakdown_framework' => $byFramework['liste'] ?? [],
            'breakdown_matrix'    => $byMatrix['liste']    ?? [],
            'details'             => $details['liste']     ?? [],
        ], Response::HTTP_OK);
    }

    /**
     * [Description for peintureProjetAnomalieDetails]
     * Récupère le détails des anomalies pour chaque type
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:56:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/anomalie/details', name: 'peinture_projet_anomalie_details', methods: ['POST'])]
    public function peintureProjetAnomalieDetails(Request $request): Response
    {

        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/anomalie/details");

        /** On instancie l'entityRepository */
        $anomalieDetailsRepos = $this->em->getRepository(AnomalieDetails::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, [
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }
        /** On récupère les données pour le projet */
        $map = ['maven_key' => $maven_key];
        $details = $anomalieDetailsRepos->selectAnomalieDetailsMavenKey($map);

        if ($details['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectAnomalieDetailsMavenKey.', [
                'code' => $details['code'],
                'erreur' => $details['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $details['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectAnomalieDetailsMavenKey (Erreur {$details['code']}).",
                'trace' => $details['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        /** La liste peut être vide (projet sans anomalie) : $row = [] dans ce cas */
        $row = $details['liste'][0] ?? [];

        $bugBlocker = $row['bug_blocker'] ?? null;
        $bugCritical = $row['bug_critical'] ?? null;
        $bugMajor = $row['bug_major'] ?? null;
        $bugMinor = $row['bug_minor'] ?? null;
        $bugInfo = $row['bug_info'] ?? null;

        $vulnerabilityBlocker = $row['vulnerability_blocker'] ?? null;
        $vulnerabilityCritical = $row['vulnerability_critical'] ?? null;
        $vulnerabilityMajor = $row['vulnerability_major'] ?? null;
        $vulnerabilityMinor = $row['vulnerability_minor'] ?? null;
        $vulnerabilityInfo = $row['vulnerability_info'] ?? null;

        $codeSmellBlocker = $row['code_smell_blocker'] ?? null;
        $codeSmellCritical = $row['code_smell_critical'] ?? null;
        $codeSmellMajor = $row['code_smell_major'] ?? null;
        $codeSmellMinor = $row['code_smell_minor'] ?? null;
        $codeSmellInfo = $row['code_smell_info'] ?? null;

        // Le total des code smell n'est pas remonté par Sonar, on le calcule à partir de la répartition par sévérité.
        $codeSmellTotal = (int) ($row['code_smell_blocker'] ?? 0)
                + (int) ($row['code_smell_critical'] ?? 0)
                + (int) ($row['code_smell_major'] ?? 0)
                + (int) ($row['code_smell_minor'] ?? 0)
                + (int) ($row['code_smell_info'] ?? 0);
        // on considère que les code smell de sévérité "info" ne sont pas des vrais code smell (pas de dette technique associée)
        $codeSmellRealTotal = (int) ($codeSmellTotal - (int) ($row['code_smell_info'] ?? 0));

        return new JsonResponse([
            "code" => 200,
            "bugBlocker" => $bugBlocker,
            "bugCritical" => $bugCritical,
            "bugMajor" => $bugMajor,
            "bugMinor" => $bugMinor,
            "bugInfo" => $bugInfo,
            "vulnerabilityBlocker" => $vulnerabilityBlocker,
            "vulnerabilityCritical" => $vulnerabilityCritical,
            "vulnerabilityMajor" => $vulnerabilityMajor,
            "vulnerabilityMinor" => $vulnerabilityMinor,
            "vulnerabilityInfo" => $vulnerabilityInfo,
            "CodeSmellRealTotal" => $codeSmellRealTotal,
            "codeSmellBlocker" => $codeSmellBlocker,
            "codeSmellCritical" => $codeSmellCritical,
            "codeSmellMajor" => $codeSmellMajor,
            "codeSmellMinor" => $codeSmellMinor,
            "codeSmellInfo" => $codeSmellInfo
        ], Response::HTTP_OK);
    }

    /**
     * [Description for peintureProjetHotspots]
     * Récupère les hotspots du projet
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:56:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/hotspots', name: 'peinture_projet_hotspots', methods: ['POST'])]
    public function peintureProjetHotspots(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/hotspots");

        /** On instancie l'entityRepository */
        $hotspotsRepos = $this->em->getRepository(Hotspots::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, [
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot au statut TO_REVIEW */
        $map = ['maven_key' => $maven_key, 'status' => 'TO_REVIEW'];
        $toReview = $hotspotsRepos->countHotspotsStatus($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($toReview['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête countHotspotsStatus (TO_REVIEW).', [
                'code' => $toReview['code'],
                'erreur' => $toReview['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $toReview['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour countHotspotsStatus avec TO_REVIEW (Erreur {$toReview['code']}).",
                'trace' => $toReview['erreur'] ?? self::$noData,
                'test' => 'TO_REVIEW'
            ], Response::HTTP_OK);
        }

        /** On compte le nombre de hotspot au statut REVIEWED */
        $map = ['maven_key' => $maven_key, 'status' => 'REVIEWED'];
        $reviewed = $hotspotsRepos->countHotspotsStatus($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($reviewed['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête countHotspotsStatus (REVIEWED).', [
                'code' => $reviewed['code'],
                'erreur' => $reviewed['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $reviewed['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour countHotspotsStatus avec REVIEWED (Erreur {$reviewed['code']}).",
                'trace' => $reviewed['erreur'] ?? self::$noData,
                'test' => 'REVIEWED'
            ], Response::HTTP_OK);
        }

        /** On calcul la note sonar
         *  La requête countHotspotsStatus retourne le COUNT(*) sous l'alias 'nombre'.
         *  La note suit la convention SonarQube security_review_rating :
         *  ratio reviewed / (reviewed + toReview) * 100 -> A:>=80 B:>=70 C:>=50 D:>=30 E:<30.
         */
        $nbToReview = (int) ($toReview['nombre'][0]['nombre'] ?? 0);
        $nbReviewed = (int) ($reviewed['nombre'][0]['nombre'] ?? 0);
        $note = $this->calculNoteHotspot($nbToReview, $nbReviewed);

        return new JsonResponse([
            'code' => 200,
            'note' => $note
        ], Response::HTTP_OK);
    }

    /**
     * [Description for peintureProjetHotspotsDetails]
     * Récupère le détails des hotspots du projet
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:57:40 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/hotspots/details', name: 'peinture_projet_hotspots_details', methods: ['POST'])]
    public function peintureProjetHotspotsDetails(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/hotspots/details");

        /** On instancie l'entityRepository */
        $hotspotsRepos = $this->em->getRepository(Hotspots::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'data' => $request->getContent()
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, [
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** retourne une liste par niveau (1,2,3) du nombre de hotspot à vérifier */
        $map = ['maven_key' => $maven_key, 'status' => 'TO_REVIEW'];
        $getToReview = $hotspotsRepos->selectHotspotsByNiveau($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($getToReview['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectHotspotsByNiveau (TO_REVIEW).', [
                'code' => $getToReview['code'],
                'erreur' => $getToReview['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $getToReview['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectHotspotsByNiveau avec TO_REVIEW (Erreur {$getToReview['code']}).",
                'trace' => $getToReview['erreur'] ?? self::$noData,
                'test' => 'TO_REVIEW'
            ], Response::HTTP_OK);
        }

        $map = ['maven_key' => $maven_key, 'status' => 'REVIEWED'];
        $getReviewed = $hotspotsRepos->selectHotspotsByNiveau($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($getReviewed['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectHotspotsByNiveau (REVIEWED).', [
                'code' => $getReviewed['code'],
                'erreur' => $getReviewed['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $getReviewed['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectHotspotsByNiveau avec REVIEWED (Erreur {$getReviewed['code']}).",
                'trace' => $getReviewed['erreur'] ?? self::$noData,
                'test' => 'REVIEWED'
            ], Response::HTTP_OK);
        }

        $toReview = $this->vulnerabilityProbability($getToReview['liste']);
        $reviewed = $this->vulnerabilityProbability($getReviewed['liste']);

        return new JsonResponse(
            [
                'code' => 200,
                'to_review_total' => $toReview['total'] ?? 0,
                'to_review_high' => $toReview['high'] ?? 0,
                'to_review_medium' => $toReview['medium'] ?? 0,
                'to_review_low' => $toReview['low'] ?? 0,
                'reviewed_total' => $reviewed['total'] ?? 0,
                'reviewed_high' => $reviewed['high'] ?? 0,
                'reviewed_medium' => $reviewed['medium'] ?? 0,
                'reviewed_low' => $reviewed['low'] ?? 0
            ],
            Response::HTTP_OK
        );
    }

    /**
     * [Description for peintureProjetNoSonar]
     * Récupère les exclusions noSonar et suppressWarning pour Java
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:58:42 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/nosonar', name: 'peinture_projet_nosonar', methods: ['POST'])]
    public function peintureProjetNoSonar(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/nosonar");

        /** On instancie l'entityRepository */
        $noSonarRepos = $this->em->getRepository(NoSonar::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'data' => $request->getContent()
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, [
                'maven_key' => $maven_key
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On récupère la liste des règles et leur nombre pour un projet. */
        $map = ['maven_key' => $maven_key];

        /** On revoie une liste avec la rule ["java:NoSonar",  "java:S1309"] et le total */
        $rules = $noSonarRepos->selectNoSonarRuleGroupByRule($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($rules['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectNoSonarRuleGroupByRule.', [
                'code' => $rules['code'],
                'erreur' => $rules['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $rules['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectNoSonarRuleGroupByRule (Erreur {$rules['code']}).",
                'trace' => $rules['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        $suppressWarning = $noPmd = $checkStyle = 0;
        $javaNoSonar = $pythonNoSonar = $phpNoSonar = 0;
        if (!empty($rules)) {
            foreach ($rules['liste'] as $rule) {
                switch ($rule['rule']) {
                    case 'java:S1309':
                        $suppressWarning = (int) $rule['total'];
                        break;
                    case 'java:S1310':
                        $noPmd = (int) $rule['total'];
                        break;
                    case 'java:S1315':
                        $checkStyle = (int) $rule['total'];
                        break;
                    case 'java:NoSonar':
                        $javaNoSonar = (int) $rule['total'];
                        break;
                    case 'python:NoSonar':
                        $pythonNoSonar = (int) $rule['total'];
                        break;
                    case 'php:NoSonar':
                        $phpNoSonar = (int) $rule['total'];
                        break;
                    default:
                        $this->logger->critical("[SWITCH-NOSONAR] 🔴 On ne devrait pas arriver dans le default du switch.", ['case' => $rule['rule'] ?? self::$noData]);
                        break;
                }
            }
        }
        $totalNoSonar = $javaNoSonar + $pythonNoSonar + $phpNoSonar;
        $total = $suppressWarning + $noPmd + $checkStyle + $totalNoSonar;

        /* MODIF 2026-05-06 : ajout de la liste détaillée
         * (rule + component + line) pour alimenter le tableau "Liste des fichiers"
         * de la modale Detail NoSonar. */
        $details = $noSonarRepos->selectNoSonarComponentOrderByRule($map);
        if ($details['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectNoSonarComponentOrderByRule.', [
                'code' => $details['code'],
                'erreur' => $details['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $details['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectNoSonarComponentOrderByRule (Erreur {$details['code']}).",
                'trace' => $details['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        return new JsonResponse(
            [
                'code' => 200,
                'total' => $total,
                's1309' => $suppressWarning,
                'nosonar' => $javaNoSonar,
                'suppress_warning' => $suppressWarning,
                'no_pmd' => $noPmd,
                'check_style' => $checkStyle,
                'java_no_sonar' => $javaNoSonar,
                'python_no_sonar' => $pythonNoSonar,
                'php_no_sonar' => $phpNoSonar,
                'total_no_sonar' => $totalNoSonar,
                'details' => $details,
            ],
            Response::HTTP_OK
        );
    }

    /**
     * [Description for peintureProjetTodo]
     * Récupère les tags to do pour : java, js, ts, html et xml
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 10/04/2023, 17:54:08 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/todo', name: 'peinture_projet_todo', methods: ['POST'])]
    public function peintureProjetTodo(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/todo");

        /** On instancie l'entityRepository */
        $todoRepos = $this->em->getRepository(Todo::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'data' => $request->getContent()
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, ['maven_key' => $maven_key]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On récupère la liste des to do pour le projet. */
        $map = ['maven_key' => $maven_key];
        $rules = $todoRepos->selectTodoRuleGroupByRule($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($rules['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectTodoRuleGroupByRule.', [
                'code' => $rules['code'],
                'erreur' => $rules['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $rules['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectTodoRuleGroupByRule (Erreur {$rules['code']}).",
                'trace' => $rules['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        /* MODIF 2026-05-06 : ajout python, php, ruby
         * (l'API renvoyait 5 langages, modale incomplete cote front).
         * `web` correspond au scope HTML/JSP/templates SonarQube (rule Web:S1135). */
        $todo = $java = $javascript = $typescript = $html = $xml = 0;
        $python = $php = $ruby = 0;

        if (!empty($rules)) {
            /** On récupère le nombre total de To do par langage. strtolower car
             *  Sonar renvoie parfois `php:s1135` minuscule, parfois `Web:S1135`. */
            foreach ($rules['liste'] as $rule) {
                $r = strtolower((string) ($rule['rule'] ?? ''));
                $total = (int) ($rule['total'] ?? 0);
                switch ($r) {
                    case 'java:s1135':
                        $java = $total;
                        break;
                    case 'javascript:s1135':
                        $javascript = $total;
                        break;
                    case 'typescript:s1135':
                        $typescript = $total;
                        break;
                    case 'web:s1135':
                        $html = $total;
                        break;
                    case 'xml:s1135':
                        $xml = $total;
                        break;
                    case 'python:s1135':
                        $python = $total;
                        break;
                    case 'php:s1135':
                        $php = $total;
                        break;
                    case 'ruby:s1135':
                        $ruby = $total;
                        break;
                    default:
                        $this->logger->critical("[SWITCH-TODO] 🔴 On ne devrait pas arriver dans le default du switch.", ['case' => $r]);
                        break;
                }
            }
            $todo = $java + $javascript + $typescript + $html + $xml + $python + $php + $ruby;
        }

        /** On récupère la liste détaillée. */
        $map = ['maven_key' => $maven_key];
        $details = $todoRepos->selectTodoComponentOrderByRule($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern.
         * Bug corrigé au passage : le message utilisait $rules['code'] au lieu de $details['code']. */
        if ($details['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectTodoComponentOrderByRule.', [
                'code' => $details['code'],
                'erreur' => $details['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $details['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectTodoComponentOrderByRule (Erreur {$details['code']}).",
                'trace' => $details['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        return new JsonResponse(
            [
                'code' => 200,
                'todo' => $todo,
                'java' => $java,
                'javascript' => $javascript,
                'typescript' => $typescript,
                'html' => $html,
                'xml' => $xml,
                /* MODIF 2026-05-06 : ajout python/php/ruby */
                'python' => $python,
                'php' => $php,
                'ruby' => $ruby,
                'details' => $details
            ],
            Response::HTTP_OK
        );
    }

    /**
     * [Description for peintureProjetLogger]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 12/09/2024 21:57:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/peinture/projet/logger', name: 'peinture_projet_logger', methods: ['POST'])]
    public function peintureProjetLogger(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/peinture/projet/logger");

        /** On instancie l'entityRepository */
        $loggerRepository = $this->em->getRepository(Logger::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'data' => $request->getContent()
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On nettoie la clé */
        $maven_key = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');

        /** On regarde si le projet existe */
        $isValide = $this->isValideMavenKey->isValideInformation($maven_key);
        if ($isValide['code'] === 404) {
            $this->logger->warning(self::$loggerE404, ['maven_key' => $maven_key]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => self::$erreur404,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On récupère la liste des règles et leur nombre pour un projet. */
        $map = ['maven_key' => $maven_key];
        $logger = $loggerRepository->selectLogger($map);
        /* MODIF 2026-05-04 : ajout logger->error + alignement pattern. */
        if ($logger['code'] !== 200) {
            $this->logger->error('[Collecte-Peinture] ❌ Échec de la requête selectLogger.', [
                'code' => $logger['code'],
                'erreur' => $logger['erreur'] ?? self::$noData,
                'maven_key' => $maven_key,
            ]);
            return new JsonResponse([
                'code' => $logger['code'],
                'type' => 'error',
                'message' => "❌ La récupération des données a échouée pour selectLogger (Erreur {$logger['code']}).",
                'trace' => $logger['erreur'] ?? self::$noData,
            ], Response::HTTP_OK);
        }

        /* MODIF 2026-05-04 : sentinel -1 → null.
         * `null` signifie "pas de données" (plugin SonarQube logger inactif sur ce projet),
         * différent de `0` qui signifie "0 logger collecté".
         * Le frontend (displayHelper.displayNumber) affiche '-' pour null et '0' pour 0. */
        $loggerInfo = $loggerWarn = $loggerError = $loggerDebug = $total = null;
        $loggerInfoPct = $loggerWarnPct = $loggerErrorPct = $loggerDebugPct = null;

        if (!empty($logger['liste'])) {
            $loggerInfo = $logger['liste']['logger_info'] ?? 0;
            $loggerWarn = $logger['liste']['logger_warn'] ?? 0;
            $loggerError = $logger['liste']['logger_error'] ?? 0;
            $loggerDebug = $logger['liste']['logger_debug'] ?? 0;

            /** Calcul la somme des loggers */
            $total = (int) $loggerInfo + (int) $loggerWarn + (int) $loggerError + (int) $loggerDebug;

            /* Calcul de la répartition en pourcentage pour chaque niveau de logger. */
            $loggerInfoPct = $total > 0 ? round(($loggerInfo / $total) * 100, 2) : 0;
            $loggerWarnPct = $total > 0 ? round(($loggerWarn / $total) * 100, 2) : 0;
            $loggerErrorPct = $total > 0 ? round(($loggerError / $total) * 100, 2) : 0;
            $loggerDebugPct = $total > 0 ? round(($loggerDebug / $total) * 100, 2) :   0;
        }

        return new JsonResponse([
            'code' => 200,
            'total' => $total,
            'logger_info' => $loggerInfo,
            'logger_warn' => $loggerWarn,
            'logger_error' => $loggerError,
            'logger_debug' => $loggerDebug,
            'logger_info_pct' => $loggerInfoPct,
            'logger_warn_pct' => $loggerWarnPct,
            'logger_error_pct' => $loggerErrorPct,
            'logger_debug_pct' => $loggerDebugPct
        ], Response::HTTP_OK);
    }

    /**
     * [Description for parseLanguageDistribution]
     * Parse la valeur de ncloc_language_distribution de SonarQube.
     * Formats acceptes :
     *   - SonarQube 2026 :  "java=1234;ruby=56;xml=12"  (equal + semicolon)
     *   - Variante          :  "java:1234,ruby:56"       (colon + comma)
     *   - Ancien JSON       :  {"java":1234,"ruby":56}
     *   - null / vide       :  []
     *
     * @return array<string,int>  ex: ['java' => 1234, 'ruby' => 56]
     *
     * Created at: 12/07/2026 11:44:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function parseLanguageDistribution(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        // Tentative 1 : JSON (ancien format)
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_map('intval', $decoded);
        }

        // Tentative 2 : format texte "lang<SEP>value<SEP>..."
        // Séparateurs entre paires : ; ou ,
        // Séparateurs clef/valeur : = ou :
        $pairs = preg_split('/[;,]/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($pairs as $pair) {
            if (preg_match('/^\s*([^=:]+)\s*[=:]\s*(\d+)\s*$/', $pair, $m)) {
                $result[trim($m[1])] = (int) $m[2];
            }
        }
        return $result;
    }
}
