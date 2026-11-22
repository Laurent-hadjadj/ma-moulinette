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

declare(strict_types=1);

namespace App\Controller\NpmAudit;

use App\Controller\Traits\AppUserAware;
use App\Entity\NpmVulnerability;
use App\Repository\{NpmDependencyRepository, NpmFindingRepository, NpmScanRepository};
use App\Service\MesProjets;
use App\Service\PdfExportService;
use App\Service\UserAgent\UserAgentTrackingFacade;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Pages web npm audit — même rôle (`ROLE_SECURITY`) et même anti-IDOR
 * (périmètre `groupe_fonctionnel`) que DependencyCheckPageController, mais
 * périmètre réduit au lot 1 du chantier : liste des paquets scannés, détail
 * d'un scan (+ rapport PDF), un dashboard par scan. Une seule page sort de
 * ce cadre "projet par projet" : `paquets()`, vue cross-projets gardée par
 * `ROLE_SECURITY_ANALYTICS` (pas juste `ROLE_SECURITY`), pas d'anti-IDOR
 * par périmètre puisqu'elle est volontairement globale.
 *
 * Pages :
 *  - /npm-audit                                        -> index : liste des paquets scannés
 *  - /npm-audit/projet/{group}/{artifact}/{version}     -> detail d'un scan
 *  - /npm-audit/projet/{group}/{artifact}/{version}/pdf -> rapport PDF du scan
 *  - /npm-audit/dashboard/{group}/{artifact}/{version}  -> graphiques du scan
 *  - /npm-audit/paquets                                 -> vue cross-projets par paquet (ROLE_SECURITY_ANALYTICS)
 */
#[IsGranted('ROLE_SECURITY')]
class NpmAuditPageController extends AbstractController
{
    use AppUserAware;

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    private const REGEX = '[A-Za-z0-9._-]+';

    public function __construct(
        ParameterBagInterface $params,
        private readonly NpmScanRepository $scanRepository,
        private readonly NpmFindingRepository $findingRepository,
        private readonly NpmDependencyRepository $dependencyRepository,
        private readonly LoggerInterface $logger,
        private readonly MesProjets $mesProjets,
        private readonly UserAgentTrackingFacade $tracking,
        private readonly PdfExportService $pdfExportService,
    ) {
        $this->logoEntreprise        = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong  = $params->get('marque.entreprise.long');
        $this->environnement         = $params->get('environnement');
        $this->version               = $params->get('version');
        $this->dateCopyright         = date('Y');
    }

    /**
     * @return array<string, mixed>
     */
    private function genericRender(): array
    {
        return [
            'type_footer'             => null,
            'logo_entreprise'         => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long'  => $this->marqueEntrepriseLong,
            'env'                     => $this->environnement,
            'version'                 => $this->version,
            'date_copyright'          => $this->dateCopyright,
        ];
    }

    /**
     * Même pattern que DependencyCheckPageController::allowedMavenKeys() —
     * ensemble des "{group}:{artifact}" accessibles à l'utilisateur connecté
     * selon ses groupes_fonctionnels.
     *
     * @return array<string, true>|null
     */
    private function allowedMavenKeys(): ?array
    {
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        if (empty($groupes)) {
            return null;
        }
        $mesProjets = $this->mesProjets->liste($groupes);
        if (($mesProjets['code'] ?? 500) !== 200 || !isset($mesProjets['projets'])) {
            return null;
        }
        $set = [];
        foreach ($mesProjets['projets'] as $projet) {
            if (isset($projet['id'])) {
                $set[(string) $projet['id']] = true;
            }
        }
        return $set;
    }

    /**
     * Anti-IDOR : la clé npm "{group}:{artifact}" doit appartenir au
     * périmètre groupe_fonctionnel de l'utilisateur connecté.
     */
    private function isProjectAccessible(string $group, string $artifact): bool
    {
        $allowed = $this->allowedMavenKeys();
        if ($allowed === null) {
            return false;
        }
        return isset($allowed["{$group}:{$artifact}"]);
    }

    #[Route('/npm-audit', name: 'npm_audit_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->tracking->track('NPM_AUDIT_INDEX');

        $render = $this->genericRender();

        $allowed = $this->allowedMavenKeys();
        if ($allowed === null) {
            $this->addFlash('notice', [
                'type'    => 'info',
                'message' => 'Aucun projet accessible dans votre périmètre groupe_fonctionnel.',
            ]);
            $render['scans'] = [];
            return $this->render('npm_audit/index.html.twig', $render);
        }

        $allowedKeys = array_keys($allowed);
        $scans = array_values(array_filter(
            $this->scanRepository->listLatestPerProject(),
            static fn(array $row) => in_array($row['project_group'] . ':' . $row['project_artifact'], $allowedKeys, true)
        ));

        $render['scans'] = $scans;

        return $this->render('npm_audit/index.html.twig', $render);
    }

    /**
     * Vue cross-projets : pour chaque paquet npm touché par au moins un
     * avis de sécurité, nombre de projets distincts concernés (sur le
     * dernier scan de chacun). Gardée par `ROLE_SECURITY_ANALYTICS` en plus
     * de `ROLE_SECURITY` (classe) — volontairement globale, pas d'anti-IDOR
     * par périmètre groupe_fonctionnel : c'est une vue organisation entière
     * pour le pilotage sécurité, pas une page "par projet".
     */
    #[IsGranted('ROLE_SECURITY_ANALYTICS')]
    #[Route('/npm-audit/paquets', name: 'npm_audit_paquets', methods: ['GET'])]
    public function paquets(): Response
    {
        $this->tracking->track('NPM_AUDIT_PAQUETS');

        $render = $this->genericRender();

        $result = $this->dependencyRepository->listProjectImpact(200);
        if ($result['code'] !== 200) {
            $this->logger->error('[NpmAudit] ❌ Échec de la requête listProjectImpact.', [
                'code'   => $result['code'],
                'erreur' => $result['erreur'],
            ]);
            $this->addFlash('notice', [
                'type'    => 'error',
                'message' => sprintf('La récupération des paquets a échoué (Erreur %s).', $result['code']),
            ]);
            $render['packages'] = [];
            $render['kpi'] = ['scanned_projects' => 0, 'impacted_packages' => 0, 'total_findings' => 0, 'shared_packages' => 0];
            $render['top_chart'] = ['labels' => [], 'counts' => []];
            return $this->render('npm_audit/paquets.html.twig', $render);
        }

        $packages = array_map(static fn(array $row) => [
            'name'            => (string) $row['name'],
            'nb_projects'     => (int) $row['nb_projects'],
            'nb_findings'     => (int) $row['nb_findings'],
            'worst_severity'  => self::severityFromRank((int) $row['worst_severity_rank']),
        ], $result['liste']);

        $render['packages'] = $packages;
        $render['kpi'] = [
            'scanned_projects'   => count($this->scanRepository->listLatestPerProject()),
            'impacted_packages'  => count($packages),
            'total_findings'     => array_sum(array_column($packages, 'nb_findings')),
            'shared_packages'    => count(array_filter($packages, static fn(array $p) => $p['nb_projects'] >= 2)),
        ];

        $top = array_slice($packages, 0, 10);
        $render['top_chart'] = [
            'labels' => array_column($top, 'name'),
            'counts' => array_column($top, 'nb_projects'),
        ];

        return $this->render('npm_audit/paquets.html.twig', $render);
    }

    private static function severityFromRank(int $rank): string
    {
        return match ($rank) {
            5 => NpmVulnerability::SEVERITY_CRITICAL,
            4 => NpmVulnerability::SEVERITY_HIGH,
            3 => NpmVulnerability::SEVERITY_MODERATE,
            2 => NpmVulnerability::SEVERITY_LOW,
            default => NpmVulnerability::SEVERITY_INFO,
        };
    }

    #[Route(
        '/npm-audit/projet/{group}/{artifact}/{version}',
        name: 'npm_audit_projet',
        methods: ['GET'],
        requirements: ['group' => self::REGEX, 'artifact' => self::REGEX, 'version' => self::REGEX]
    )]
    public function projet(string $group, string $artifact, string $version): Response
    {
        $this->tracking->track('NPM_AUDIT_PROJET');

        $render = $this->genericRender();
        $render['project_group']    = $group;
        $render['project_artifact'] = $artifact;
        $render['project_version']  = $version;

        if (!$this->isProjectAccessible($group, $artifact)) {
            throw $this->createAccessDeniedException(
                sprintf('Projet %s:%s hors du périmètre du groupe_fonctionnel.', $group, $artifact)
            );
        }

        $scan = $this->scanRepository->findLatestForProject($group, $artifact, $version);
        if ($scan === null) {
            $this->addFlash('notice', [
                'type'    => 'warning',
                'message' => sprintf(
                    'Aucun scan npm audit trouvé pour %s:%s:%s. Vérifier que la CI a bien envoyé un rapport.',
                    $group,
                    $artifact,
                    $version
                ),
            ]);
            $render['scan'] = null;
            $render['findings'] = [];
            $render['package_summary'] = [];
            return $this->render('npm_audit/projet.html.twig', $render);
        }

        $render['scan'] = $scan;

        $result = $this->findingRepository->listForScan((int) $scan->getId());
        if ($result['code'] !== 200) {
            $this->logger->error('[NpmAudit] ❌ Échec de la requête listForScan.', [
                'scan_id' => (int) $scan->getId(),
                'code'    => $result['code'],
                'erreur'  => $result['erreur'],
            ]);
            $this->addFlash('notice', [
                'type'    => 'error',
                'message' => sprintf('La récupération des vulnérabilités a échoué (Erreur %s). %s', $result['code'], $result['erreur']),
            ]);
            $render['findings'] = [];
        } else {
            $render['findings'] = $result['liste'];
        }

        $render['package_summary'] = $this->buildPackageSummary($render['findings']);

        return $this->render('npm_audit/projet.html.twig', $render);
    }

    #[Route(
        '/npm-audit/projet/{group}/{artifact}/{version}/pdf',
        name: 'npm_audit_projet_pdf',
        methods: ['GET'],
        requirements: ['group' => self::REGEX, 'artifact' => self::REGEX, 'version' => self::REGEX]
    )]
    public function projetPdf(string $group, string $artifact, string $version, Request $request): Response
    {
        $this->tracking->track('NPM_AUDIT_PROJET_PDF');

        if (!$this->isProjectAccessible($group, $artifact)) {
            throw $this->createAccessDeniedException(
                sprintf('Projet %s:%s hors du périmètre du groupe_fonctionnel.', $group, $artifact)
            );
        }

        $scan = $this->scanRepository->findLatestForProject($group, $artifact, $version);
        if ($scan === null) {
            $this->addFlash('notice', [
                'type'    => 'warning',
                'message' => sprintf(
                    'Aucun scan npm audit trouvé pour %s:%s:%s. Impossible de générer le rapport PDF.',
                    $group,
                    $artifact,
                    $version
                ),
            ]);
            return $this->redirectToRoute('npm_audit_projet', ['group' => $group, 'artifact' => $artifact, 'version' => $version]);
        }

        $scanId = (int) $scan->getId();

        $findings = [];
        $findingsError = false;
        $result = $this->findingRepository->listForScan($scanId);
        if ($result['code'] !== 200) {
            $findingsError = true;
            $this->logger->error('[NpmAudit] ❌ Échec de la requête listForScan (PDF).', [
                'scan_id' => $scanId,
                'code'    => $result['code'],
                'erreur'  => $result['erreur'],
            ]);
        } else {
            $findings = $result['liste'];
        }

        $packageSummary = $this->buildPackageSummary($findings);

        $documentType = (string) $request->query->get('type', 'Document confidentiel');
        $wmParam = strtolower((string) $request->query->get('watermark', ''));
        $watermarkOverride = match ($wmParam) {
            'on', '1', 'true', 'yes' => true,
            'off', '0', 'false', 'no' => false,
            default => null,
        };

        $pdf = $this->pdfExportService->generateNpmAuditPdf(
            [
                'project_group'         => $group,
                'project_artifact'      => $artifact,
                'project_version'       => $version,
                'scan'                  => $scan,
                'findings'              => $findings,
                'findings_error'        => $findingsError,
                'origin_package_counts' => $this->countPackagesByOrigin($packageSummary),
                'history'               => $this->scanRepository->listHistoryByProject($group, $artifact, 10),
                'origin_chart'          => $this->buildStackedSeverityChartData(
                    $this->findingRepository->countByOriginAndSeverity($scanId),
                    ['prod' => 'Production', 'dev' => 'Dev', 'unknown' => 'Indéterminé'],
                    $scanId,
                    'countByOriginAndSeverity',
                ),
                'direct_chart'          => $this->buildStackedSeverityChartData(
                    $this->findingRepository->countByDirectAndSeverity($scanId),
                    ['direct' => 'Direct', 'transitive' => 'Transitif'],
                    $scanId,
                    'countByDirectAndSeverity',
                ),
                'top_packages'          => $this->buildTopPackagesChartData($scanId),
            ],
            $documentType,
            $watermarkOverride,
        );

        $filename = sprintf('rapport-npm-audit-%s-%s.pdf', $artifact, $version);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Décompte des paquets de `package_summary` par origine — pour les
     * tuiles de la page Résumé du rapport PDF (pas le total brut
     * `metadata.vulnerabilities` de npm audit, qui compte des paquets
     * différemment — voir la note affichée en page Focus stratégique).
     *
     * @param array<int, array{name: string, is_direct: bool, is_prod: ?bool, cvss_max: ?float, cwe_count: int}> $packageSummary
     *
     * @return array{prod: int, dev: int, unknown: int}
     */
    private function countPackagesByOrigin(array $packageSummary): array
    {
        $counts = ['prod' => 0, 'dev' => 0, 'unknown' => 0];
        foreach ($packageSummary as $p) {
            if ($p['is_prod'] === null) {
                $counts['unknown']++;
            } elseif ($p['is_prod']) {
                $counts['prod']++;
            } else {
                $counts['dev']++;
            }
        }
        return $counts;
    }

    /**
     * Tableau de synthèse par paquet (1 ligne par paquet distinct ayant au
     * moins un finding — PAS le "N paquets" de `metadata.vulnerabilities`
     * de npm audit, qui peut inclure un paquet signalé uniquement par
     * propagation depuis une dépendance vulnérable, sans avis de sécurité
     * qui lui soit propre (via[] uniquement des chaînes, filtré par
     * l'ingester : aucun NpmFinding créé pour ce paquet). isDirect/isProd
     * sont identiques pour tous les findings d'un même paquet dans un même
     * scan (valeurs reprises du paquet source, pas du finding) : prendre la
     * première rencontrée suffit. cvss = le pire (max) parmi ses findings ;
     * cwe_count = nombre de CWE distincts cumulés sur tous ses findings.
     *
     * @param array<int, array<string, mixed>> $findings
     *
     * @return array<int, array{name: string, is_direct: bool, is_prod: ?bool, cvss_max: ?float, cwe_count: int}>
     */
    private function buildPackageSummary(array $findings): array
    {
        $byName = [];
        foreach ($findings as $f) {
            $name = (string) $f['dependency_name'];
            if (!isset($byName[$name])) {
                $byName[$name] = [
                    'name'      => $name,
                    'is_direct' => (bool) $f['is_direct'],
                    'is_prod'   => $f['is_prod'],
                    'cvss_max'  => null,
                    'cwes'      => [],
                ];
            }
            if ($f['cvss'] !== null) {
                $byName[$name]['cvss_max'] = $byName[$name]['cvss_max'] === null
                    ? (float) $f['cvss']
                    : max($byName[$name]['cvss_max'], (float) $f['cvss']);
            }
            foreach ((array) $f['cwes'] as $cwe) {
                $byName[$name]['cwes'][(string) $cwe] = true;
            }
        }

        $summary = [];
        foreach ($byName as $row) {
            $summary[] = [
                'name'      => $row['name'],
                'is_direct' => $row['is_direct'],
                'is_prod'   => $row['is_prod'],
                'cvss_max'  => $row['cvss_max'],
                'cwe_count' => count($row['cwes']),
            ];
        }
        usort($summary, static fn(array $a, array $b) => strcmp($a['name'], $b['name']));

        return $summary;
    }

    #[Route(
        '/npm-audit/dashboard/{group}/{artifact}/{version}',
        name: 'npm_audit_dashboard',
        methods: ['GET'],
        requirements: ['group' => self::REGEX, 'artifact' => self::REGEX, 'version' => self::REGEX]
    )]
    public function dashboard(string $group, string $artifact, string $version): Response
    {
        $this->tracking->track('NPM_AUDIT_DASHBOARD');

        $render = $this->genericRender();
        $render['project_group']    = $group;
        $render['project_artifact'] = $artifact;
        $render['project_version']  = $version;

        if (!$this->isProjectAccessible($group, $artifact)) {
            throw $this->createAccessDeniedException(
                sprintf('Projet %s:%s hors du périmètre du groupe_fonctionnel.', $group, $artifact)
            );
        }

        $scan = $this->scanRepository->findLatestForProject($group, $artifact, $version);
        if ($scan === null) {
            $this->addFlash('notice', [
                'type'    => 'warning',
                'message' => sprintf(
                    'Aucun scan npm audit trouvé pour %s:%s:%s.',
                    $group,
                    $artifact,
                    $version
                ),
            ]);
            $render['scan'] = null;
            $render['chart_data'] = null;
            return $this->render('npm_audit/dashboard.html.twig', $render);
        }

        $render['scan'] = $scan;

        $scanId = (int) $scan->getId();

        $render['chart_data'] = [
            'severity' => [
                'critical' => $scan->getVulnCountCritical(),
                'high'     => $scan->getVulnCountHigh(),
                'moderate' => $scan->getVulnCountModerate(),
                'low'      => $scan->getVulnCountLow(),
                'info'     => $scan->getVulnCountInfo(),
            ],
            'origin'      => $this->buildStackedSeverityChartData(
                $this->findingRepository->countByOriginAndSeverity($scanId),
                ['prod' => 'Production', 'dev' => 'Dev', 'unknown' => 'Indéterminé'],
                $scanId,
                'countByOriginAndSeverity',
            ),
            'direct'      => $this->buildStackedSeverityChartData(
                $this->findingRepository->countByDirectAndSeverity($scanId),
                ['direct' => 'Direct', 'transitive' => 'Transitif'],
                $scanId,
                'countByDirectAndSeverity',
            ),
            'topPackages' => $this->buildTopPackagesChartData($scanId),
        ];

        return $this->render('npm_audit/dashboard.html.twig', $render);
    }

    /**
     * Transforme le résultat de `countByOriginAndSeverity()`/`countByDirectAndSeverity()`
     * (lignes {group_key, severity, cnt}) en structure prête pour un bar
     * chart empilé par sévérité côté JS : {labels: [...], severities: {sev
     * => [compte par label, dans le même ordre que labels]}}. En cas
     * d'échec de la requête (loggé), retourne une structure vide plutôt que
     * de faire échouer toute la page dashboard pour un seul graphique.
     *
     * @param array{code: 200, liste: array<int, array<string, mixed>>, erreur: string}|array{code: 500|23505, erreur: string} $result
     * @param array<string, string> $groupLabels clé brute (group_key) => libellé affiché, dans l'ordre d'affichage souhaité
     *
     * @return array{labels: array<int, string>, severities: array<string, array<int, int>>}
     */
    private function buildStackedSeverityChartData(array $result, array $groupLabels, int $scanId, string $method): array
    {
        $labels = array_values($groupLabels);
        $severityOrder = ['critical', 'high', 'moderate', 'low', 'info'];
        $severities = array_fill_keys($severityOrder, array_fill(0, count($labels), 0));

        if ($result['code'] !== 200) {
            $this->logger->error(sprintf('[NpmAudit] ❌ Échec de la requête %s.', $method), [
                'scan_id' => $scanId,
                'code'    => $result['code'],
                'erreur'  => $result['erreur'],
            ]);
            return ['labels' => $labels, 'severities' => $severities];
        }

        $groupKeys = array_keys($groupLabels);
        $matrix = [];
        foreach ($result['liste'] as $row) {
            $matrix[(string) $row['group_key']][(string) $row['severity']] = (int) $row['cnt'];
        }

        foreach ($severityOrder as $sev) {
            foreach ($groupKeys as $i => $gk) {
                $severities[$sev][$i] = $matrix[$gk][$sev] ?? 0;
            }
        }

        return ['labels' => $labels, 'severities' => $severities];
    }

    /**
     * @return array{labels: array<int, string>, counts: array<int, int>}
     */
    private function buildTopPackagesChartData(int $scanId): array
    {
        $result = $this->findingRepository->topVulnerableDependenciesForScan($scanId, 10);

        if ($result['code'] !== 200) {
            $this->logger->error('[NpmAudit] ❌ Échec de la requête topVulnerableDependenciesForScan.', [
                'scan_id' => $scanId,
                'code'    => $result['code'],
                'erreur'  => $result['erreur'],
            ]);
            return ['labels' => [], 'counts' => []];
        }

        return [
            'labels' => array_map(static fn(array $r) => (string) $r['name'], $result['liste']),
            'counts' => array_map(static fn(array $r) => (int) $r['finding_count'], $result['liste']),
        ];
    }
}
