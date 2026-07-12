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

namespace App\Tests\Unit\Controller\Owasp;

use App\Controller\Owasp\OwaspController;
use App\Entity\{HotspotDetails, HotspotOwasp, Historique, Owasp, OwaspTop10};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Repository\{DcScanRepository, HistoriqueRepository, HotspotDetailsRepository, HotspotOwaspRepository, OwaspRepository, OwaspTop10Repository};
use App\Service\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

/**
 * MODIF 2026-05-10 : adaptation a la nouvelle signature
 * `index(Request $request)` + couverture du flux token (missing / invalide /
 * happy path) + mock du second repository OwaspRepository::selectOwaspVersion.
 */
#[AllowMockObjectsWithoutExpectations]
class OwaspControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */   private MockObject $params;
    /** @var EntityManagerInterface&MockObject */  private MockObject $em;
    /** @var LoggerInterface&MockObject */         private MockObject $logger;
    /** @var PdfExportService&MockObject */        private MockObject $pdfExport;
    /** @var DcScanRepository&MockObject */        private MockObject $dcScanRepository;
    /** @var OwaspTop10Repository&MockObject */      private MockObject $repoTop10;
    /** @var OwaspRepository&MockObject */           private MockObject $repoOwasp;
    /** @var HotspotOwaspRepository&MockObject */   private MockObject $repoHotspotOwasp;
    /** @var HotspotDetailsRepository&MockObject */ private MockObject $repoHotspotDetails;
    /** @var HistoriqueRepository&MockObject */      private MockObject $repoHistorique;
    /** @var Environment&MockObject */               private MockObject $twig;
    /** @var FlashBag&MockObject */                  private MockObject $flashBag;

    /** @var UserAgentTrackingFacade&MockObject */   private MockObject $tracking;

    private OwaspController $controller;

    /** Maven key projet de test pour les tokens valides. */
    private const MAVEN_KEY = 'fr.ma-moulinette:le-chat';

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pdfExport = $this->createMock(PdfExportService::class);
        $this->dcScanRepository = $this->createMock(DcScanRepository::class);
        $this->repoTop10          = $this->createMock(OwaspTop10Repository::class);
        $this->repoOwasp          = $this->createMock(OwaspRepository::class);
        $this->repoHotspotOwasp   = $this->createMock(HotspotOwaspRepository::class);
        $this->repoHotspotDetails = $this->createMock(HotspotDetailsRepository::class);
        $this->repoHistorique     = $this->createMock(HistoriqueRepository::class);
        $this->twig               = $this->createMock(Environment::class);
        $this->flashBag           = $this->createMock(FlashBag::class);
        $this->tracking           = $this->createMock(UserAgentTrackingFacade::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['sonar.version', '2026'],
            ['sonar.url', 'https://sonar.example.com'],
        ]);

        /* MODIF 2026-05-17 : dispatch étendu
         * aux repos rapportPdf (HotspotOwasp / HotspotDetails / Historique). */
        $this->em->method('getRepository')->willReturnCallback(
            fn(string $class) => match ($class) {
                OwaspTop10::class     => $this->repoTop10,
                Owasp::class          => $this->repoOwasp,
                HotspotOwasp::class   => $this->repoHotspotOwasp,
                HotspotDetails::class => $this->repoHotspotDetails,
                Historique::class     => $this->repoHistorique,
                default               => $this->repoTop10,
            }
        );

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['twig', true],
            ['request_stack', true],
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new OwaspController(
            $this->params,
            $this->em,
            $this->logger,
            $this->pdfExport,
            $this->dcScanRepository,
            $this->tracking,
        );
        $this->controller->setContainer($container);
    }

    /**
     * Génère un token valide (rot13(base64("{salt}|{mavenKey}"))).
     */
    private function buildValidToken(string $mavenKey = self::MAVEN_KEY, int $salt = 12345): string
    {
        return str_rot13(base64_encode("{$salt}|{$mavenKey}"));
    }

    /**
     * Construit une Request avec ?token=... (ou sans token si null).
     */
    private function buildRequest(?string $token): Request
    {
        return new Request($token === null ? [] : ['token' => $token]);
    }

    /* ============ Token : missing / invalide ============ */

    public function testIndexFlashesErrorWhenTokenIsMissing(): void
    {
        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(
                fn($v) => $v['type'] === 'error'
                    && str_contains($v['message'], 'Erreur 400')
            ));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->anything())
            ->willReturn('<html>missing-token</html>');

        $response = $this->controller->index($this->buildRequest(null));

        $this->assertSame('<html>missing-token</html>', $response->getContent());
    }

    public function testIndexFlashesErrorWhenTokenIsInvalidBase64(): void
    {
        // Token mal forme : str_rot13 d'une chaîne qui n'est pas du base64 valide
        $invalidToken = '###not-base64-rot13###';

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>invalid-token</html>');

        $this->controller->index($this->buildRequest($invalidToken));
    }

    public function testIndexFlashesErrorWhenTokenHasWrongFormat(): void
    {
        // Token decodable mais sans le pipe '|' attendu (parts != 2)
        $token = str_rot13(base64_encode('un-seul-segment-sans-pipe'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>bad-format</html>');

        $this->controller->index($this->buildRequest($token));
    }

    /* ============ Référentiels OwaspTop10 ============ */

    public function testIndexFlashesAndRendersWhen2017Fails(): void
    {
        $this->repoTop10->expects($this->once())
            ->method('selectOwaspTop10Referential')
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->anything())
            ->willReturn('<html>err-2017</html>');

        $response = $this->controller->index($this->buildRequest($this->buildValidToken()));

        $this->assertSame('<html>err-2017</html>', $response->getContent());
    }

    public function testIndexFlashesAndRendersWhen2021Fails(): void
    {
        $this->repoTop10->method('selectOwaspTop10Referential')->willReturnOnConsecutiveCalls(
            ['code' => 200, 'liste' => [['a' => 1]]], // 2017 OK
            ['code' => 500, 'erreur' => 'fail-2021'],  // 2021 FAIL
        );

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => str_contains($v['message'], 'fail-2021')));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>err-2021</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));
    }

    public function testIndexWarnsWhenAllReferentialsAreEmpty(): void
    {
        $this->repoTop10->method('selectOwaspTop10Referential')->willReturn([
            'code' => 200, 'liste' => [],
        ]);
        // selectOwaspVersion appelé apres les 3 référentiels
        $this->repoOwasp->method('selectOwaspVersion')->willReturn([
            'code' => 200, 'application' => self::MAVEN_KEY, 'version' => '1.0.0', 'erreur' => '',
        ]);

        // 1 add (warning référentiels vides). selectOwaspVersion = 200 -> pas d'autre flash.
        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>empty</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));
    }

    /* MODIF 2026-05-10 : adaptation au flow token + mock
     * selectOwaspVersion (nouveau second repo OwaspRepository). */
    public function testIndexHappyPathInjectsReferentialsAndApplicationVersion(): void
    {
        $owasp2017 = ['code' => 200, 'liste' => [['menace' => 1]]];
        $owasp2021 = ['code' => 200, 'liste' => [['menace' => 2]]];
        $owasp2025 = ['code' => 200, 'liste' => [['menace' => 3]]];
        $this->repoTop10->method('selectOwaspTop10Referential')
            ->willReturnOnConsecutiveCalls($owasp2017, $owasp2021, $owasp2025);

        $this->repoOwasp->expects($this->once())
            ->method('selectOwaspVersion')
            ->with(self::MAVEN_KEY)
            ->willReturn([
                'code'        => 200,
                'application' => self::MAVEN_KEY,
                'version'     => '1.4.2',
                'erreur'      => '',
            ]);

        $this->flashBag->expects($this->never())->method('add');

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));

        /* MODIF 2026-05-06 : sonar_version est passe en int. */
        $this->assertSame(2026, $capturedCtx['sonar_version']);
        $this->assertSame('https://sonar.example.com', $capturedCtx['serveur']);
        $this->assertSame($owasp2017, $capturedCtx['owasp_2017']);
        $this->assertSame($owasp2021, $capturedCtx['owasp_2021']);
        $this->assertSame($owasp2025, $capturedCtx['owasp_2025']);
        /* MODIF 2026-05-10 : split maven_key en 2 clés
         * (group + artifact). `application` = artifact (cf. breadcrumb),
         * `application_group` = group (nécessaire pour le lien dc_projet).
         * `application_version` = version projet (a ne pas confondre avec
         * `version` qui reste la version de l'app injectée par genericRender). */
        $this->assertSame('fr.ma-moulinette', $capturedCtx['application_group']);
        $this->assertSame('le-chat', $capturedCtx['application']);
        $this->assertSame('1.4.2', $capturedCtx['application_version']);
        /* MODIF 2026-05-11 : sans mock explicite,
         * findLatestByMavenKey retourne null -> has_dc_scan = false et
         * dc_link_* tous a null. */
        $this->assertFalse($capturedCtx['has_dc_scan']);
        $this->assertNull($capturedCtx['dc_link_group']);
        $this->assertNull($capturedCtx['dc_link_artifact']);
        $this->assertNull($capturedCtx['dc_link_version']);
    }

    /* MODIF 2026-05-11 : quand un scan DC existe pour
     * le maven_key, has_dc_scan=true et dc_link_* sont peuples depuis le scan
     * DC (pas depuis le breadcrumb OWASP). Le bouton DC s'affiche meme si le
     * breadcrumb OWASP est absent (cas reel : table `owasp` vide pour ce projet). */
    public function testIndexHasDcScanTrueWithDcLinkCoordinatesFromScan(): void
    {
        $this->repoTop10->method('selectOwaspTop10Referential')->willReturn([
            'code' => 200, 'liste' => [['menace' => 1]],
        ]);
        // Breadcrumb OWASP absent (cas reel) : application=null, version=null
        $this->repoOwasp->method('selectOwaspVersion')->willReturn([
            'code'        => 200,
            'application' => null,
            'version'     => null,
            'erreur'      => '',
        ]);

        $scanMock = $this->createMock(\App\Entity\DcScan::class);
        $scanMock->method('getProjectGroup')->willReturn('fr.ma-moulinette');
        $scanMock->method('getProjectArtifact')->willReturn('le-chat');
        $scanMock->method('getProjectVersion')->willReturn('1.4.2');

        $this->dcScanRepository->expects($this->once())
            ->method('findLatestByMavenKey')
            ->with(self::MAVEN_KEY)
            ->willReturn($scanMock);

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));

        $this->assertTrue($capturedCtx['has_dc_scan']);
        $this->assertSame('fr.ma-moulinette', $capturedCtx['dc_link_group']);
        $this->assertSame('le-chat', $capturedCtx['dc_link_artifact']);
        $this->assertSame('1.4.2', $capturedCtx['dc_link_version']);
        // application_* restent null (breadcrumb absent) -> validation du découplage
        $this->assertNull($capturedCtx['application']);
        $this->assertNull($capturedCtx['application_version']);
    }

    public function testIndexFlashesErrorButRendersWhenSelectOwaspVersionFails(): void
    {
        $this->repoTop10->method('selectOwaspTop10Referential')->willReturn([
            'code' => 200, 'liste' => [['menace' => 1]],
        ]);
        // selectOwaspVersion en erreur DB
        $this->repoOwasp->expects($this->once())
            ->method('selectOwaspVersion')
            ->willReturn(['code' => 500, 'erreur' => 'db fail version']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(
                fn($v) => $v['type'] === 'error' && str_contains($v['message'], 'db fail version')
            ));

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok-but-no-version</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));

        /* MODIF 2026-05-10 : page dégradée = les 3 clés
         * project-scoped (group / artifact / version) sont null. La cle
         * `version` (genericRender) reste a '2.0.0' (version de l'app), donc
         * on n'asserte PAS dessus. Le template cache le bouton DC quand
         * application_group/artifact/version est null. */
        $this->assertNull($capturedCtx['application_group']);
        $this->assertNull($capturedCtx['application']);
        $this->assertNull($capturedCtx['application_version']);
    }

    /* MODIF 2026-05-10 : selectOwaspVersion peut renvoyer
     * code=200 + application=null (cas no-data : aucune ligne pour la
     * maven_key dans la table owasp). Le controller doit accepter ce
     * scenario sans planter (garde-fou is_string sur explode) et exposer
     * les 3 clés project-scoped a null pour que le template cache le
     * bouton dc. */
    public function testIndexHandlesNoDataBreadcrumbWithoutCrashing(): void
    {
        $this->repoTop10->method('selectOwaspTop10Referential')->willReturn([
            'code' => 200, 'liste' => [['menace' => 1]],
        ]);
        $this->repoOwasp->expects($this->once())
            ->method('selectOwaspVersion')
            ->willReturn(['code' => 200, 'application' => null, 'version' => null, 'erreur' => '']);

        // code=200 -> pas de flash error.
        $this->flashBag->expects($this->never())->method('add');

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>no-data-breadcrumb</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));

        $this->assertNull($capturedCtx['application_group']);
        $this->assertNull($capturedCtx['application']);
        $this->assertNull($capturedCtx['application_version']);
    }

    /* ============ Helpers privés ============ */

    /** Invoque une méthode privée statique de OwaspController via Reflection. */
    private static function callStatic(string $method, mixed ...$args): mixed
    {
        $ref = new \ReflectionMethod(OwaspController::class, $method);
        return $ref->invoke(null, ...$args);
    }

    /**
     * Stubs minimalistes pour rapportPdf : tous les repos renvoient un code
     * d'erreur, pdfExport retourne une chaîne PDF fictive.
     */
    private function setupRapportPdfRepos(): void
    {
        $this->repoOwasp->method('selectOwaspOrderByDateEnregistrement')
            ->willReturn(['code' => 404]);
        $this->repoHotspotOwasp->method('countHotspotOwaspStatus')
            ->willReturn(['request' => [['nombre' => 0]]]);
        $this->repoHotspotOwasp->method('countHotspotOwaspProbability')
            ->willReturn(['code' => 404]);
        $this->repoHotspotOwasp->method('countHotspotOwaspMenaces')
            ->willReturn(['code' => 404]);
        $this->repoHotspotDetails->method('selectHotspotDetailsByStatus')
            ->willReturn(['code' => 404]);
        $this->repoHistorique->method('selectHistoriqueProjetLast')
            ->willReturn(['code' => 404]);
    }

    /* ============ computeFailleRating ============ */

    /* MODIF 2026-05-17 : tests helpers statiques privés. */

    public function testComputeFailleRatingReturnsE(): void
    {
        $this->assertSame('E', self::callStatic('computeFailleRating',
            ['blocker' => 2, 'critical' => 0, 'major' => 0, 'minor' => 0]));
    }

    public function testComputeFailleRatingReturnsD(): void
    {
        $this->assertSame('D', self::callStatic('computeFailleRating',
            ['blocker' => 0, 'critical' => 2, 'major' => 0, 'minor' => 0]));
    }

    public function testComputeFailleRatingReturnsC(): void
    {
        $this->assertSame('C', self::callStatic('computeFailleRating',
            ['blocker' => 0, 'critical' => 0, 'major' => 2, 'minor' => 0]));
    }

    public function testComputeFailleRatingReturnsB(): void
    {
        $this->assertSame('B', self::callStatic('computeFailleRating',
            ['blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 2]));
    }

    public function testComputeFailleRatingReturnsAWhenAllZero(): void
    {
        $this->assertSame('A', self::callStatic('computeFailleRating',
            ['blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 0]));
    }

    /* ============ computeHotspotNoteFromTaux ============ */

    public function testComputeHotspotNoteFromTauxReturnsA(): void
    {
        $this->assertSame('A', self::callStatic('computeHotspotNoteFromTaux', 0.80));
    }

    public function testComputeHotspotNoteFromTauxReturnsB(): void
    {
        $this->assertSame('B', self::callStatic('computeHotspotNoteFromTaux', 0.75));
    }

    public function testComputeHotspotNoteFromTauxReturnsC(): void
    {
        $this->assertSame('C', self::callStatic('computeHotspotNoteFromTaux', 0.60));
    }

    public function testComputeHotspotNoteFromTauxReturnsD(): void
    {
        $this->assertSame('D', self::callStatic('computeHotspotNoteFromTaux', 0.40));
    }

    public function testComputeHotspotNoteFromTauxReturnsE(): void
    {
        $this->assertSame('E', self::callStatic('computeHotspotNoteFromTaux', 0.20));
    }

    /* ============ computeHotspotRating ============ */

    public function testComputeHotspotRatingReturnsAWhenTotalIsZero(): void
    {
        $this->assertSame('A', self::callStatic('computeHotspotRating', 5, 0));
    }

    public function testComputeHotspotRatingDelegatesToNoteFromTaux(): void
    {
        // count=1, total=10 → taux=0.9 > 0.79 → 'A'
        $this->assertSame('A', self::callStatic('computeHotspotRating', 1, 10));
        // count=9, total=10 → taux=0.1 ≤ 0.31 → 'E'
        $this->assertSame('E', self::callStatic('computeHotspotRating', 9, 10));
    }

    /* ============ extractProjectName ============ */

    public function testExtractProjectNameReturnsLastSegment(): void
    {
        $this->assertSame('le-chat', self::callStatic('extractProjectName', 'fr.ma-moulinette:le-chat'));
    }

    public function testExtractProjectNameReturnsSelfWhenNoColon(): void
    {
        $this->assertSame('paddpm', self::callStatic('extractProjectName', 'paddpm'));
    }

    /* ============ owaspLabels ============ */

    public function testOwaspLabelsContains2017And2021WithTenLabelsEach(): void
    {
        $labels = self::callStatic('owaspLabels');
        $this->assertArrayHasKey(2017, $labels);
        $this->assertArrayHasKey(2021, $labels);
        $this->assertCount(10, $labels[2017]);
        $this->assertCount(10, $labels[2021]);
        $this->assertSame("Attaques d'injection", $labels[2017][1]);
        $this->assertSame("Contrôle d'accès défaillant", $labels[2021][1]);
    }

    /* ============ rapportPdf ============ */

    /* MODIF 2026-05-17 : tests rapportPdf. */

    public function testRapportPdfThrowsNotFoundWhenMavenKeyIsEmpty(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->controller->rapportPdf(new Request());
    }

    public function testRapportPdfNormalizesInvalidReferentialTo2021(): void
    {
        $this->setupRapportPdfRepos();

        $capturedData = null;
        $this->pdfExport->method('generateOwaspPdf')->willReturnCallback(
            function (array $data) use (&$capturedData) {
                $capturedData = $data;
                return '%PDF-minimal';
            }
        );

        $this->controller->rapportPdf(new Request(['maven_key' => self::MAVEN_KEY, 'referential_owasp' => '9999']));

        $this->assertSame(2021, $capturedData['referential_owasp']);
    }

    public function testRapportPdfHappyPathReturnsPdfResponse(): void
    {
        $this->setupRapportPdfRepos();
        $this->pdfExport->method('generateOwaspPdf')->willReturn('%PDF-minimal');

        $response = $this->controller->rapportPdf(new Request(['maven_key' => self::MAVEN_KEY]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function testRapportPdfDownloadTrueUsesAttachment(): void
    {
        $this->setupRapportPdfRepos();
        $this->pdfExport->method('generateOwaspPdf')->willReturn('%PDF-minimal');

        $response = $this->controller->rapportPdf(new Request([
            'maven_key' => self::MAVEN_KEY,
            'download'  => '1',
        ]));

        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function testRapportPdfFallsBackToHistoriqueWhenOwaspDataIsEmpty(): void
    {
        // Mocks explicites (pas de helper) pour éviter le pre-stub FIFO sur repoHistorique.
        $this->repoOwasp->method('selectOwaspOrderByDateEnregistrement')->willReturn(['code' => 404]);
        $this->repoHotspotOwasp->method('countHotspotOwaspStatus')->willReturn(['request' => [['nombre' => 0]]]);
        $this->repoHotspotOwasp->method('countHotspotOwaspProbability')->willReturn(['code' => 404]);
        $this->repoHotspotOwasp->method('countHotspotOwaspMenaces')->willReturn(['code' => 404]);
        $this->repoHotspotDetails->method('selectHotspotDetailsByStatus')->willReturn(['code' => 404]);
        $this->repoHistorique->method('selectHistoriqueProjetLast')->willReturn([
            'code' => 200,
            'infos' => [['version' => '1.5.0', 'date_version' => '2026-01-01']],
        ]);

        $capturedData = null;
        $this->pdfExport->method('generateOwaspPdf')->willReturnCallback(
            function (array $data) use (&$capturedData) {
                $capturedData = $data;
                return '%PDF-minimal';
            }
        );

        $this->controller->rapportPdf(new Request(['maven_key' => self::MAVEN_KEY]));

        $this->assertSame('1.5.0', $capturedData['version']);
        $this->assertSame('2026-01-01', $capturedData['date_version']);
    }

    public function testRapportPdfPopulatesHotspotProbabilityBreakdown(): void
    {
        // Mocks explicites (pas de helper) pour éviter le pre-stub FIFO sur countHotspotOwaspStatus.
        $this->repoOwasp->method('selectOwaspOrderByDateEnregistrement')->willReturn(['code' => 404]);
        $this->repoHotspotOwasp->method('countHotspotOwaspStatus')
            ->willReturnOnConsecutiveCalls(
                ['request' => [['nombre' => 8]]],  // REVIEWED
                ['request' => [['nombre' => 2]]],  // TO_REVIEW
            );
        $this->repoHotspotOwasp->method('countHotspotOwaspProbability')->willReturn([
            'code'   => 200,
            'nombre' => [
                ['probability' => 'HIGH',   'total' => 5],
                ['probability' => 'MEDIUM', 'total' => 3],
            ],
        ]);
        $this->repoHotspotOwasp->method('countHotspotOwaspMenaces')->willReturn(['code' => 404]);
        $this->repoHotspotDetails->method('selectHotspotDetailsByStatus')->willReturn(['code' => 404]);
        $this->repoHistorique->method('selectHistoriqueProjetLast')->willReturn(['code' => 404]);

        $capturedData = null;
        $this->pdfExport->method('generateOwaspPdf')->willReturnCallback(
            function (array $data) use (&$capturedData) {
                $capturedData = $data;
                return '%PDF-minimal';
            }
        );

        $this->controller->rapportPdf(new Request(['maven_key' => self::MAVEN_KEY]));

        $this->assertSame(8,   $capturedData['hotspots']['reviewed']);
        $this->assertSame(2,   $capturedData['hotspots']['to_review']);
        $this->assertSame(5,   $capturedData['hotspots']['high']);
        $this->assertSame(3,   $capturedData['hotspots']['medium']);
        // taux = 1 − (2/10) = 0.8 > 0.79 → 'A'
        $this->assertSame('A', $capturedData['hotspots']['note']);
    }

    public function testRapportPdfBuildsTenCategoriesWithRatings(): void
    {
        $ligne = ['version' => '1.0', 'date_version' => '2026-01-01'];
        for ($i = 1; $i <= 10; $i++) {
            $ligne["a{$i}"]          = ($i === 1) ? 5 : 0;
            $ligne["a{$i}_blocker"]  = ($i === 1) ? 2 : 0;  // A1 : blocker=2 → E
            $ligne["a{$i}_critical"] = 0;
            $ligne["a{$i}_major"]    = 0;
            $ligne["a{$i}_minor"]    = 0;
        }
        $this->repoOwasp->method('selectOwaspOrderByDateEnregistrement')
            ->willReturn(['code' => 200, 'liste' => [$ligne]]);
        $this->repoHotspotOwasp->method('countHotspotOwaspStatus')
            ->willReturn(['request' => [['nombre' => 0]]]);
        $this->repoHotspotOwasp->method('countHotspotOwaspProbability')->willReturn(['code' => 404]);
        $this->repoHotspotOwasp->method('countHotspotOwaspMenaces')->willReturn(['code' => 404]);
        $this->repoHotspotDetails->method('selectHotspotDetailsByStatus')->willReturn(['code' => 404]);

        $capturedData = null;
        $this->pdfExport->method('generateOwaspPdf')->willReturnCallback(
            function (array $data) use (&$capturedData) {
                $capturedData = $data;
                return '%PDF-minimal';
            }
        );

        $this->controller->rapportPdf(new Request(['maven_key' => self::MAVEN_KEY, 'referential_owasp' => '2021']));

        $this->assertCount(10, $capturedData['categories']);
        $cat1 = $capturedData['categories'][0];
        $this->assertSame(1,                           $cat1['id']);
        $this->assertSame(5,                           $cat1['faille']);
        $this->assertSame('E',                         $cat1['faille_rating']);
        $this->assertSame("Contrôle d'accès défaillant", $cat1['label']);  // 2021 A1
        $this->assertSame('le-chat',                   $capturedData['project_name']);
    }

    /* ============ details ============ */

    public function testDetailsFlashesAndRendersIndexWhenRepoFails(): void
    {
        $this->repoTop10->expects($this->once())
            ->method('selectOwaspTop10Details')
            ->with(['menace' => 5])
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->anything())
            ->willReturn('<html>err</html>');

        $this->controller->details(5);
    }

    public function testDetailsHappyPathRendersDetailWithMenace(): void
    {
        $this->repoTop10->method('selectOwaspTop10Details')->willReturn([
            'code' => 200,
            'details' => [['menace' => 5, 'titre' => 'Injection']],
        ]);

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/detail.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>detail</html>');

        $this->controller->details(5);

        $this->assertSame(5, $capturedCtx['menace']);
        $this->assertSame('Injection', $capturedCtx['owasp']['titre']);
    }
}
