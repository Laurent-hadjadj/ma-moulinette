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

declare(strict_types=1);

namespace App\Tests\Unit\Controller\DependencyCheck;

use App\Controller\DependencyCheck\DependencyCheckPageController;
use App\Entity\{DcScan, Utilisateur};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Repository\{DcCveRepository, DcDependencyRepository, DcFindingRepository, DcScanRepository};
use App\Service\DependencyCheck\DcExecutiveAnalyticsService;
use App\Service\{MesProjets, PdfExportService};
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * MODIF 2026-06-07 : tests des 17 méthodes de route
 * non couvertes de DependencyCheckPageController.
 * Stratégie : analytics mode = true pour la majorité (évite appUser()),
 * user mode uniquement pour index() qui appelle allowedMavenKeys() sans garde.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(DependencyCheckPageController::class)]
class DependencyCheckPageControllerTest extends TestCase
{
    private const GROUP    = 'fr.ma-moulinette';
    private const ARTIFACT = 'ma-moulinette';
    private const VERSION  = '1.2.3';

    /** @var DcScanRepository&MockObject */       private MockObject $scanRepo;
    /** @var DcCveRepository&MockObject */        private MockObject $cveRepo;
    /** @var DcDependencyRepository&MockObject */ private MockObject $depRepo;
    /** @var DcFindingRepository&MockObject */    private MockObject $findingRepo;
    /** @var PdfExportService&MockObject */       private MockObject $pdfService;
    /** @var PaginatorInterface&MockObject */     private MockObject $paginator;
    /** @var LoggerInterface&MockObject */        private MockObject $logger;
    /** @var MesProjets&MockObject */             private MockObject $mesProjets;
    private DcExecutiveAnalyticsService $dcAnalytics;

    /** @var AuthorizationCheckerInterface&MockObject */ private MockObject $authChecker;
    /** @var TokenStorageInterface&MockObject */         private MockObject $tokenStorage;
    /** @var Environment&MockObject */                  private MockObject $twig;
    /** @var FlashBagInterface&MockObject */            private MockObject $flashBag;
    /** @var Session&MockObject */                      private MockObject $session;
    /** @var RequestStack&MockObject */                 private MockObject $requestStack;
    /** @var RouterInterface&MockObject */               private MockObject $router;

    /** @var UserAgentTrackingFacade&MockObject */      private MockObject $tracking;

    private DependencyCheckPageController $controller;

    protected function setUp(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['logo.entreprise',        'logo.png'],
            ['marque.entreprise.short', 'ACME'],
            ['marque.entreprise.long', 'ACME Corp'],
            ['environnement',          'test'],
            ['version',                '0.0.0'],
        ]);

        $this->scanRepo    = $this->createMock(DcScanRepository::class);
        $this->cveRepo     = $this->createMock(DcCveRepository::class);
        $this->depRepo     = $this->createMock(DcDependencyRepository::class);
        $this->findingRepo = $this->createMock(DcFindingRepository::class);
        $this->pdfService  = $this->createMock(PdfExportService::class);
        $this->paginator   = $this->createMock(PaginatorInterface::class);
        $this->logger      = $this->createMock(LoggerInterface::class);
        $this->mesProjets  = $this->createMock(MesProjets::class);
        $this->dcAnalytics = new DcExecutiveAnalyticsService();

        $this->authChecker  = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->twig = $this->createMock(Environment::class);
        $this->twig->method('render')->willReturn('<html>ok</html>');
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->session  = $this->createMock(Session::class);
        $this->session->method('getFlashBag')->willReturn($this->flashBag);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->method('getSession')->willReturn($this->session);

        $this->paginator->method('paginate')
            ->willReturn($this->createMock(PaginationInterface::class));

        $this->router = $this->createMock(RouterInterface::class);
        $this->router->method('generate')->willReturn('/dependency-check/projet/fr.ma-moulinette/ma-moulinette/1.2.3');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool =>
            in_array($id, [
                'twig',
                'request_stack',
                'security.authorization_checker',
                'security.token_storage',
                'router',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig',                             $this->twig],
            ['request_stack',                    $this->requestStack],
            ['security.authorization_checker',   $this->authChecker],
            ['security.token_storage',           $this->tokenStorage],
            ['router',                           $this->router],
        ]);

        $this->controller = new DependencyCheckPageController(
            $params,
            $this->scanRepo,
            $this->cveRepo,
            $this->depRepo,
            $this->findingRepo,
            $this->pdfService,
            $this->paginator,
            $this->logger,
            $this->dcAnalytics,
            $this->mesProjets,
            $this->tracking,
        );
        $this->controller->setContainer($container);
    }

    /* =========================================================
     * Helpers
     * ========================================================= */

    private function analyticsMode(): void
    {
        $this->authChecker->method('isGranted')
            ->willReturnCallback(fn(string $attr): bool => $attr === 'ROLE_SECURITY_ANALYTICS');
    }

    /**
     * Stub un utilisateur non-analytics avec des groupes fonctionnels donnés.
     *
     * @param array<int, string> $groupes
     */
    private function stubUser(array $groupes = []): void
    {
        $user = $this->createMock(Utilisateur::class);
        $user->method('getListeGroupeFonctionnel')->willReturn($groupes);
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->authChecker->method('isGranted')->willReturn(false);
    }

    /**
     * Stub analytics mode + user valide.
     * dashboard() appelle appUser() (L601) avant la garde isAnalyticsMode(),
     * donc les deux doivent être stubés ensemble pour éviter le conflit sur
     * authChecker->isGranted().
     *
     * @param array<int, string> $groupes
     */
    private function stubAnalyticsWithUser(array $groupes = []): void
    {
        $user = $this->createMock(Utilisateur::class);
        $user->method('getListeGroupeFonctionnel')->willReturn($groupes);
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->authChecker->method('isGranted')
            ->willReturnCallback(fn(string $attr): bool => $attr === 'ROLE_SECURITY_ANALYTICS');
    }

    /**
     * Retourne un mock DcScan avec les compteurs standards.
     *
     * @param array{
     *     id?: int,
     *     group?: string,
     *     artifact?: string,
     *     version?: string,
     *     scan_date?: \DateTimeImmutable,
     *     parent_label?: ?string,
     *     parent_version?: ?string,
     *     archetype_version?: ?string,
     *     dep_count_total?: int,
     *     dep_count_vulnerable?: int,
     *     cve_count_total?: int,
     *     cve_count_critical?: int,
     *     cve_count_high?: int,
     *     cve_count_medium?: int,
     *     cve_count_low?: int
     * } $opts
     */
    private function makeDcScan(array $opts = []): DcScan&MockObject
    {
        $scan = $this->createMock(DcScan::class);
        $scan->method('getId')->willReturn($opts['id'] ?? 1);
        $scan->method('getProjectGroup')->willReturn($opts['group'] ?? self::GROUP);
        $scan->method('getProjectArtifact')->willReturn($opts['artifact'] ?? self::ARTIFACT);
        $scan->method('getProjectVersion')->willReturn($opts['version'] ?? self::VERSION);
        $scan->method('getScanDate')->willReturn($opts['scan_date'] ?? new \DateTimeImmutable('2026-01-01'));
        $scan->method('getParentLabel')->willReturn($opts['parent_label'] ?? null);
        $scan->method('getParentVersion')->willReturn($opts['parent_version'] ?? null);
        $scan->method('getArchetypeVersion')->willReturn($opts['archetype_version'] ?? null);
        $scan->method('getDepCountTotal')->willReturn($opts['dep_count_total'] ?? 10);
        $scan->method('getDepCountVulnerable')->willReturn($opts['dep_count_vulnerable'] ?? 1);
        $scan->method('getCveCountTotal')->willReturn($opts['cve_count_total'] ?? 2);
        $scan->method('getCveCountCritical')->willReturn($opts['cve_count_critical'] ?? 1);
        $scan->method('getCveCountHigh')->willReturn($opts['cve_count_high'] ?? 1);
        $scan->method('getCveCountMedium')->willReturn($opts['cve_count_medium'] ?? 0);
        $scan->method('getCveCountLow')->willReturn($opts['cve_count_low'] ?? 0);
        return $scan;
    }

    /** Stub tous les repos DC pour un dashboard vide (aucune erreur). */
    private function stubDashboardReposEmpty(): void
    {
        $empty = ['code' => 200, 'liste' => []];
        $this->scanRepo->method('listLatestPerCoupleForView')->willReturn($empty);
        $this->scanRepo->method('findArchetypeScansBatch')->willReturn([]);
        $this->scanRepo->method('aggregateSeverityByDay')->willReturn($empty);
        $this->cveRepo->method('countProjectsByCwe')->willReturn($empty);
        $this->cveRepo->method('findCriticalCvesAcrossProjects')->willReturn($empty);
        $this->cveRepo->method('listTopCriticalCves')->willReturn($empty);
        $this->cveRepo->method('listNewCriticalCvesGroupedByCve')->willReturn($empty);
        $this->depRepo->method('topVulnerableDependencies')->willReturn($empty);
        $this->depRepo->method('topMutualisableDependencies')->willReturn($empty);
    }

    /* =========================================================
     * index()
     * ========================================================= */

    public function testIndexFlashesWhenUserHasNoGroups(): void
    {
        $this->stubUser([]); // groupes vides → allowedMavenKeys() = null
        $this->flashBag->expects($this->once())->method('add');

        $response = $this->controller->index(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIndexFlashesErrorWhenRepositoryFails(): void
    {
        $this->stubUser(['G1']);
        $this->mesProjets->method('liste')->willReturn([
            'code' => 200,
            'projets' => [['id' => 'G1:myapp']],
        ]);
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 500, 'erreur' => 'db fail', 'liste' => []]);
        $this->flashBag->expects($this->once())->method('add');

        $response = $this->controller->index(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIndexHappyPath(): void
    {
        $this->stubUser(['G1']);
        $this->mesProjets->method('liste')->willReturn([
            'code' => 200,
            'projets' => [['id' => 'G1:myapp']],
        ]);
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => [['project_artifact' => 'myapp']]]);

        $response = $this->controller->index(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * history()
     * ========================================================= */

    public function testHistoryThrows403WhenNotAccessible(): void
    {
        // Mode non-analytics, user sans groupes → isProjectAccessible = false.
        $this->stubUser([]);
        $this->mesProjets->method('liste')
            ->willReturn(['code' => 200, 'projets' => []]);

        $this->expectException(AccessDeniedException::class);
        $this->controller->history(self::GROUP, self::ARTIFACT);
    }

    public function testHistoryHappyPathWithDeltas(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('listHistoryForProject')->willReturn([
            'code' => 200,
            'liste' => [
                [
                    'scan_date'          => new \DateTimeImmutable('2026-01-01'),
                    'project_version'    => '1.0.0',
                    'cve_count_critical' => 2,
                    'cve_count_high'     => 3,
                    'cve_count_medium'   => 1,
                    'cve_count_low'      => 0,
                ],
                [
                    'scan_date'          => new \DateTimeImmutable('2026-02-01'),
                    'project_version'    => '1.1.0',
                    'cve_count_critical' => 1,
                    'cve_count_high'     => 2,
                    'cve_count_medium'   => 3,
                    'cve_count_low'      => 1,
                ],
            ],
        ]);

        $response = $this->controller->history(self::GROUP, self::ARTIFACT);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHistoryWithEmptyHistory(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('listHistoryForProject')
            ->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->history(self::GROUP, self::ARTIFACT);

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * projet()
     * ========================================================= */

    public function testProjetThrows403WhenNotAccessible(): void
    {
        $this->stubUser([]);
        $this->mesProjets->method('liste')
            ->willReturn(['code' => 200, 'projets' => []]);

        $this->expectException(AccessDeniedException::class);
        $this->controller->projet(self::GROUP, self::ARTIFACT, self::VERSION);
    }

    public function testProjetFlashesWhenNoScanFound(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('findLatestForProject')->willReturn(null);
        $this->flashBag->expects($this->once())->method('add');

        $response = $this->controller->projet(self::GROUP, self::ARTIFACT, self::VERSION);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProjetFlashesErrorWhenFindingsFail(): void
    {
        $this->analyticsMode();
        $scan = $this->makeDcScan();
        $this->scanRepo->method('findLatestForProject')->willReturn($scan);
        $this->findingRepo->method('listForScan')
            ->willReturn(['code' => 500, 'erreur' => 'db', 'liste' => []]);
        $this->flashBag->expects($this->once())->method('add');

        $response = $this->controller->projet(self::GROUP, self::ARTIFACT, self::VERSION);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProjetHappyPath(): void
    {
        $this->analyticsMode();
        $scan = $this->makeDcScan();
        $this->scanRepo->method('findLatestForProject')->willReturn($scan);
        $this->findingRepo->method('listForScan')
            ->willReturn(['code' => 200, 'liste' => [['cve_id' => 'CVE-2026-0001']]]);

        $response = $this->controller->projet(self::GROUP, self::ARTIFACT, self::VERSION);

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * projetPdf()
     * ========================================================= */

    public function testProjetPdfThrows403WhenNotAccessible(): void
    {
        $this->stubUser([]);
        $this->mesProjets->method('liste')
            ->willReturn(['code' => 200, 'projets' => []]);

        $this->expectException(AccessDeniedException::class);
        $this->controller->projetPdf(
            self::GROUP,
            self::ARTIFACT,
            self::VERSION,
            Request::create('/')
        );
    }

    public function testProjetPdfReturnsContentTypePdf(): void
    {
        $this->analyticsMode();
        $scan = $this->makeDcScan();
        $this->scanRepo->method('findLatestForProject')->willReturn($scan);
        $this->findingRepo->method('listForScan')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->pdfService->method('generateDcPdf')->willReturn('%PDF-1.4 fake');

        $response = $this->controller->projetPdf(
            self::GROUP,
            self::ARTIFACT,
            self::VERSION,
            Request::create('/')
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
    }

    public function testProjetPdfRedirectsWhenNoScanFound(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('findLatestForProject')->willReturn(null);
        $this->flashBag->expects($this->once())->method('add');
        $this->pdfService->expects($this->never())->method('generateDcPdf');

        $response = $this->controller->projetPdf(
            self::GROUP,
            self::ARTIFACT,
            self::VERSION,
            Request::create('/')
        );

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testProjetPdfPassesFindingsErrorFlagWhenListForScanFails(): void
    {
        $this->analyticsMode();
        $scan = $this->makeDcScan();
        $this->scanRepo->method('findLatestForProject')->willReturn($scan);
        $this->findingRepo->method('listForScan')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);
        $this->pdfService->expects($this->once())
            ->method('generateDcPdf')
            ->with($this->callback(fn(array $data) => $data['findings_error'] === true && $data['findings'] === []))
            ->willReturn('%PDF-1.4 fake');

        $response = $this->controller->projetPdf(
            self::GROUP,
            self::ARTIFACT,
            self::VERSION,
            Request::create('/')
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * executive()
     * ========================================================= */

    public function testExecutiveThrows403WhenNotAccessible(): void
    {
        $this->stubUser([]);
        $this->mesProjets->method('liste')
            ->willReturn(['code' => 200, 'projets' => []]);

        $this->expectException(AccessDeniedException::class);
        $this->controller->executive(self::GROUP, self::ARTIFACT, self::VERSION);
    }

    public function testExecutiveFlashesWhenNoScanFound(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('findLatestForProject')->willReturn(null);
        $this->flashBag->expects($this->once())->method('add');

        $response = $this->controller->executive(self::GROUP, self::ARTIFACT, self::VERSION);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testExecutiveHappyPath(): void
    {
        $this->analyticsMode();
        $scan = $this->makeDcScan();
        $this->scanRepo->method('findLatestForProject')->willReturn($scan);
        $this->findingRepo->method('listForScan')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->scanRepo->method('findArchetypeScan')->willReturn(null);
        $this->scanRepo->method('findPreviousScanSameVersion')->willReturn(null);
        $this->scanRepo->method('findPreviousScanAnyVersion')->willReturn(null);

        $response = $this->controller->executive(self::GROUP, self::ARTIFACT, self::VERSION);

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * executive() — fetchFindingsOrEmpty via prevSame != null
     * ========================================================= */

    public function testExecutiveCallsFetchFindingsForPreviousScan(): void
    {
        $this->analyticsMode();
        $scan     = $this->makeDcScan(['id' => 10]);
        $prevScan = $this->makeDcScan(['id' => 9]);
        $this->scanRepo->method('findLatestForProject')->willReturn($scan);
        $this->findingRepo->method('listForScan')->willReturn(['code' => 200, 'liste' => []]);
        $this->scanRepo->method('findArchetypeScan')->willReturn(null);
        $this->scanRepo->method('findPreviousScanSameVersion')->willReturn($prevScan);
        $this->scanRepo->method('findPreviousScanAnyVersion')->willReturn(null);

        $response = $this->controller->executive(self::GROUP, self::ARTIFACT, self::VERSION);

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * dashboard()
     * dashboard() lit $userGroupes = $this->appUser()->getListeGroupeFonctionnel()
     * avant la garde isAnalyticsMode() (L601). stubUser() est requis même en
     * analytics mode pour que appUser() ne lève pas AccessDeniedException.
     * ========================================================= */

    public function testDashboardAnalyticsModeWithEmptyData(): void
    {
        $this->stubAnalyticsWithUser([]); // analytics + appUser() requis (L601)
        $this->stubDashboardReposEmpty();

        $response = $this->controller->dashboard(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDashboardWithSocleFilterOnMatchingScans(): void
    {
        $this->stubAnalyticsWithUser([]);

        $scanRow = [
            'id'                 => 1,
            'maven_key'          => 'fr.ma-moulinette:ma-moulinette',
            'project_artifact'   => 'ma-moulinette',
            'project_version'    => '1.0',
            'parent_label'       => 'springboot-socle-config',
            'parent_version'     => '3.2.0',
            'archetype_version'  => '2.0',
            'cve_count_critical' => 1,
            'cve_count_high'     => 0,
            'cve_count_medium'   => 0,
            'cve_count_low'      => 0,
            'cve_count_total'    => 1,
            'dep_count_total'    => 10,
        ];
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => [$scanRow]]);
        $this->scanRepo->method('findArchetypeScansBatch')->willReturn([]);
        $this->scanRepo->method('aggregateSeverityByDay')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->cveRepo->method('countProjectsByCwe')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->cveRepo->method('findCriticalCvesAcrossProjects')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->cveRepo->method('listTopCriticalCves')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->cveRepo->method('listNewCriticalCvesGroupedByCve')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->depRepo->method('topVulnerableDependencies')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->depRepo->method('topMutualisableDependencies')
            ->willReturn(['code' => 200, 'liste' => []]);

        $request = Request::create('/', 'GET', ['socle' => 'springboot-socle-config@3.2.0']);
        $response = $this->controller->dashboard($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * kpi()
     * ========================================================= */

    public function testKpiAnalyticsMode(): void
    {
        $this->analyticsMode();
        $scanRow = [
            'cve_count_critical' => 0,
            'cve_count_high'     => 1,
            'cve_count_medium'   => 0,
            'cve_count_low'      => 0,
            'cve_count_total'    => 1,
            'scan_date'          => new \DateTimeImmutable('2026-01-01'),
        ];
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => [$scanRow]]);
        $this->scanRepo->method('aggregateSeverityByDay')
            ->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->kpi(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * comparer()
     * ========================================================= */

    public function testComparerWithNoAppsParamReturnsEmptyComparison(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->comparer(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testComparerWithTwoValidTripletsBuildsComparison(): void
    {
        $this->analyticsMode();
        $scan1 = $this->makeDcScan(['id' => 1, 'group' => 'g1', 'artifact' => 'a1', 'version' => 'v1']);
        $scan2 = $this->makeDcScan(['id' => 2, 'group' => 'g2', 'artifact' => 'a2', 'version' => 'v2']);
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->scanRepo->method('findScansForTriplets')->willReturn([
            'g1:a1:v1' => $scan1,
            'g2:a2:v2' => $scan2,
        ]);
        $this->cveRepo->method('findCommonCvesForScans')
            ->willReturn(['code' => 200, 'liste' => []]);

        $request = Request::create('/', 'GET', ['apps' => ['g1:a1:v1', 'g2:a2:v2']]);
        $response = $this->controller->comparer($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    /* =========================================================
     * mutualisables()
     * ========================================================= */

    public function testMutualisablesAnalyticsMode(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->depRepo->method('topMutualisableDependencies')
            ->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->mutualisables(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMutualisablesFlagsTruncatedInRenderContext(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->depRepo->method('topMutualisableDependencies')
            ->willReturn(['code' => 200, 'liste' => [], 'truncated' => true]);

        $capturedCtx = [];
        $this->twig->method('render')->willReturnCallback(function ($template, $ctx) use (&$capturedCtx) {
            $capturedCtx = $ctx;
            return '<html>ok</html>';
        });

        $response = $this->controller->mutualisables(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($capturedCtx['mutualisables_truncated']);
    }

    public function testMutualisablesNotTruncatedByDefault(): void
    {
        $this->analyticsMode();
        $this->scanRepo->method('listLatestPerCoupleForView')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->depRepo->method('topMutualisableDependencies')
            ->willReturn(['code' => 200, 'liste' => []]); // pas de clé 'truncated'

        $capturedCtx = [];
        $this->twig->method('render')->willReturnCallback(function ($template, $ctx) use (&$capturedCtx) {
            $capturedCtx = $ctx;
            return '<html>ok</html>';
        });

        $response = $this->controller->mutualisables(Request::create('/'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($capturedCtx['mutualisables_truncated']);
    }
}
