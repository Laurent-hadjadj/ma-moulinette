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

namespace App\Controller\Batch;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CleanCode;
use App\Service\{ExtractName, ClientService, UrlBuilderService};

/* MODIF 2026-05-17 : collecte des indicateurs
 * SonarQube 10+ via les facettes cleanCodeAttributeCategories,
 * impactSeverities, impactSoftwareQualities + 3 appels compliance
 * (OWASP Top 10 2021, SANS Top 25, CWE).
 * Appelé depuis ApiCollecteController::apiCollecteAnomalie() après
 * BatchCollecteAnomalieController — silencieux en cas d'échec (SQ < 10). */

/**
 * [Description BatchCollecteCleanCodeController]
 */
class BatchCollecteCleanCodeController extends AbstractController
{
    private static string $sonarUrl = "sonar.url";
    private static string $europeParis = "Europe/Paris";
    private static string $statuses = "OPEN,REOPENED";

    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private ExtractName $serviceExtractName,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {}

    /**
     * [Description for makeRequest]
     *
     * @param array<int|string, mixed> $queryParams
     * @return array<int|string, mixed>
     *
     * Created at: 17/05/2026 12:28:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function makeRequest(array $queryParams): array
    {
        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/issues/search',
            $queryParams
        );
        $this->logger->debug('[Clean Code] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            return ['code' => $result['code'], 'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'];
        }

        return $result['json'] ?? [];
    }

    /**
     * [Description for extractFacetCounts]
     * Extrait les comptages depuis un tableau de facettes API SonarQube.
     *
     * @param array<mixed> $facets
     * @param string $property
     *
     * @return array<string, int>
     *
     * Created at: 17/05/2026 12:29:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractFacetCounts(array $facets, string $property): array
    {
        foreach ($facets as $facet) {
            if (($facet['property'] ?? '') === $property) {
                $counts = [];
                foreach ($facet['values'] as $item) {
                    $counts[$item['val']] = (int) $item['count'];
                }
                return $counts;
            }
        }
        return [];
    }

    /**
     * [Description for BatchCollecteCleanCode]
     * Collecte les indicateurs SonarQube 10+ pour un projet.
     * Silencieux si le serveur SonarQube ne supporte pas les facettes 10+.
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array<int|string, mixed>
     * Created at: 17/05/2026 12:30:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteCleanCode(
        string $maven_key,
        string $mode_collecte,
        string $utilisateur_collecte
    ): array {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $cleanCodeRepos = $this->em->getRepository(CleanCode::class);

        $this->logger->info('[Clean Code] ℹ️ Début de la collecte des indicateurs qualité SonarQube 10+.', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
        ]);

        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));
        $app = $this->serviceExtractName->extractNameFromMavenKey($maven_key);

        /* ── Appel principal : nouvelles facettes SonarQube 10+ ─────── */
        $mainResult = $this->makeRequest([
            'componentKeys' => $maven_key,
            'facets' => 'cleanCodeAttributeCategories,impactSeverities,impactSoftwareQualities',
            'p' => 1, 'ps' => 1,
            'statuses' => self::$statuses,
        ]);

        if (isset($mainResult['code'])) {
            $this->logger->warning('[Clean Code] ⚠️ Facettes SonarQube 10+ non supportées — métriques clean code à zéro.', [
                'maven_key' => $maven_key,
                'code' => $mainResult['code'],
            ]);
            return ['code' => 200, 'info' => 'Facettes SonarQube 10+ non supportées.', 'skipped' => true];
        }

        /* Compatibilité SQ 8/9 (total à la racine) et SQ 10+ (paging.total). */
        $issueTotal = (int) ($mainResult['paging']['total'] ?? $mainResult['total'] ?? 0);
        $facets = $mainResult['facets'] ?? [];

        $ccCounts = $this->extractFacetCounts($facets, 'cleanCodeAttributeCategories');
        $severityCounts = $this->extractFacetCounts($facets, 'impactSeverities');
        $qualityCounts = $this->extractFacetCounts($facets, 'impactSoftwareQualities');

        /* ── Appels compliance (silencieux si non supportés) ─────────── */
        $owaspTotal = $sansTotal = $cweTotal = 0;

        $owaspResult = $this->makeRequest([
            'componentKeys' => $maven_key,
            'owaspTop10-2021' => 'a1,a2,a3,a4,a5,a6,a7,a8,a9,a10',
            'p' => 1, 'ps' => 1,
            'statuses' => self::$statuses,
        ]);
        if (!isset($owaspResult['code'])) {
            $owaspTotal = (int) ($owaspResult['paging']['total'] ?? $owaspResult['total'] ?? 0);
        }

        $sansResult = $this->makeRequest([
            'componentKeys' => $maven_key,
            'sansTop25' => 'insecure-interaction,risky-resource,porous-defenses',
            'p' => 1, 'ps' => 1,
            'statuses' => self::$statuses,
        ]);
        if (!isset($sansResult['code'])) {
            $sansTotal = (int) ($sansResult['paging']['total'] ?? $sansResult['total'] ?? 0);
        }

        /* CWE : somme des valeurs de la facette cwe (approximation). */
        $cweResult = $this->makeRequest([
            'componentKeys' => $maven_key,
            'facets' => 'cwe',
            'p' => 1, 'ps' => 1,
            'statuses' => self::$statuses,
        ]);
        if (!isset($cweResult['code'])) {
            $cweCounts = $this->extractFacetCounts($cweResult['facets'] ?? [], 'cwe');
            $cweTotal = array_sum($cweCounts);
        }

        /* ── Suppression + insertion ─────────────────────────────────── */
        $delete = $cleanCodeRepos->deleteCleanCodeMavenKey(['maven_key' => $maven_key]);
        if ($delete['code'] !== 200) {
            $this->logger->error('[Clean Code] ❌ Échec suppression.', ['erreur' => $delete['erreur']]);
            return ['code' => $delete['code'], 'erreur' => $delete['erreur']];
        }

        $map = [
            'maven_key' => $maven_key,
            'project_name' => $app,
            'issue_total' => $issueTotal,
            'cc_consistent' => $ccCounts['CONSISTENT'] ?? 0,
            'cc_intentional' => $ccCounts['INTENTIONAL'] ?? 0,
            'cc_adaptable' => $ccCounts['ADAPTABLE'] ?? 0,
            'cc_responsible' => $ccCounts['RESPONSIBLE'] ?? 0,
            'quality_maintainability' => $qualityCounts['MAINTAINABILITY'] ?? 0,
            'quality_reliability' => $qualityCounts['RELIABILITY'] ?? 0,
            'quality_security' => $qualityCounts['SECURITY'] ?? 0,
            'impact_blocker' => $severityCounts['BLOCKER'] ?? 0,
            'impact_high' => $severityCounts['HIGH'] ?? 0,
            'impact_medium' => $severityCounts['MEDIUM'] ?? 0,
            'impact_low' => $severityCounts['LOW'] ?? 0,
            'impact_info' => $severityCounts['INFO'] ?? 0,
            'owasp_top10' => $owaspTotal,
            'sans_top25' => $sansTotal,
            'cwe' => $cweTotal,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date,
        ];

        $insert = $cleanCodeRepos->insertCleanCode($map);
        if ($insert['code'] !== 200) {
            $this->logger->error('[Clean Code] ❌ Échec insertion.', ['erreur' => $insert['erreur']]);
            return ['code' => $insert['code'], 'erreur' => $insert['erreur']];
        }

        $this->logger->info('[Clean Code] ℹ️ Indicateurs collectés avec succès.', [
            'maven_key' => $maven_key,
            'issue_total' => $issueTotal,
            'cc_responsible' => $map['cc_responsible'],
            'quality_security' => $map['quality_security'],
            'owasp_top10' => $owaspTotal,
        ]);

        return [
            'code' => 200,
            'info' => "Indicateurs clean code collectés ({$issueTotal} issues).",
            'data' => $map,
            /* Données pour l'historique (colonnes à ajouter dans historique) */
            'historique' => [
                'cc_consistent' => $map['cc_consistent'],
                'cc_intentional' => $map['cc_intentional'],
                'cc_adaptable' => $map['cc_adaptable'],
                'cc_responsible' => $map['cc_responsible'],
                'quality_maintainability' => $map['quality_maintainability'],
                'quality_reliability' => $map['quality_reliability'],
                'quality_security' => $map['quality_security'],
                'impact_blocker' => $map['impact_blocker'],
                'impact_high' => $map['impact_high'],
                'impact_medium' => $map['impact_medium'],
                'impact_low' => $map['impact_low'],
                'impact_info' => $map['impact_info'],
                'owasp_top10' => $owaspTotal,
                'sans_top25' => $sansTotal,
                'cwe' => $cweTotal,
            ],
        ];
    }
}
