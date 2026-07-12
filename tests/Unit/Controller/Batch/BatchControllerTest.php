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

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchController;
use App\Entity\{BatchExecution, BatchExecutionJournal, BatchTraitement};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Repository\{BatchExecutionJournalRepository, BatchExecutionRepository, BatchTraitementRepository};
use App\Service\PdfExportService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class BatchControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var ParameterBagInterface&MockObject */           private MockObject $params;
    /** @var LoggerInterface&MockObject */                 private MockObject $logger;
    /** @var Security&MockObject */                        private MockObject $security;
    /** @var PdfExportService&MockObject */                private MockObject $pdfExportService;
    /** @var BatchTraitementRepository&MockObject */       private MockObject $batchTraitementRepo;
    /** @var BatchExecutionRepository&MockObject */        private MockObject $batchExecutionRepo;
    /** @var BatchExecutionJournalRepository&MockObject */ private MockObject $batchJournalRepo;
    /** @var AuthorizationCheckerInterface&MockObject */   private MockObject $authChecker;
    /** @var Environment&MockObject */                     private MockObject $twig;
    /** @var FlashBag&MockObject */                        private MockObject $flashBag;

    /** @var UserAgentTrackingFacade&MockObject */         private MockObject $tracking;

    private BatchController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->pdfExportService = $this->createMock(PdfExportService::class);
        $this->batchTraitementRepo = $this->createMock(BatchTraitementRepository::class);
        $this->batchExecutionRepo = $this->createMock(BatchExecutionRepository::class);
        $this->batchJournalRepo = $this->createMock(BatchExecutionJournalRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
        ]);

        $this->em->method('getRepository')->willReturnMap([
            [BatchTraitement::class, $this->batchTraitementRepo],
            [BatchExecution::class, $this->batchExecutionRepo],
            [BatchExecutionJournal::class, $this->batchJournalRepo],
        ]);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'security.authorization_checker', 'twig', 'request_stack',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
            ['twig', 1, $this->twig],
            ['request_stack', 1, $requestStack],
        ]);

        $this->controller = new BatchController(
            $this->em,
            $this->params,
            $this->logger,
            $this->security,
            $this->pdfExportService,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    /* ============ traitementInformation ============ */

    public function testInformationReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->traitementInformation($this->jsonRequest(['traitement_id' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testInformationReturns400OnInvalidJson(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->traitementInformation($this->jsonRequest('not-json'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testInformationReturns400WhenTraitementIdMissing(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->traitementInformation($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testInformationReturnsErrorWhenSelectTraitementFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->expects($this->once())
            ->method('selectBatchTraitementByTraitementId')
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $response = $this->controller->traitementInformation($this->jsonRequest(['traitement_id' => 'abc']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('db fail', $data['trace']);
    }

    public function testInformationReturnsErrorWhenSelectJobIdFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('selectBatchTraitementByTraitementId')->willReturn([
            'code' => 200,
            'traitement' => ['traitement_id' => 'abc'],
        ]);
        $this->batchExecutionRepo->expects($this->once())
            ->method('selectBatchExecutionLastTraitementId')
            ->willReturn(['code' => 500, 'erreur' => 'no job']);

        $response = $this->controller->traitementInformation($this->jsonRequest(['traitement_id' => 'abc']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testInformationHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('selectBatchTraitementByTraitementId')->willReturn([
            'code' => 200,
            'traitement' => [
                'traitement_id' => 'abc',
                'mode_collecte' => 'TRAITEMENT MANUEL',
                'success' => true,
                'titre' => 'T',
                'portefeuille' => 'P',
                'nombre_projet' => 5,
                'debut_traitement' => '2026-04-10 10:00:00',
                'fin_traitement' => '2026-04-10 10:30:00',
                'activated' => true,
                'id' => 42,
            ],
        ]);
        $this->batchExecutionRepo->method('selectBatchExecutionLastTraitementId')->willReturn([
            'code' => 200, 'id' => 7,
        ]);
        $this->batchJournalRepo->method('countBatchExecutionJournalCode')->willReturn([
            'code' => 200, 'ok' => 3, 'ko' => 1, 'oko' => 1,
        ]);
        $this->batchJournalRepo->method('selectBatchExecutionJournalNomProjetAndStatus')->willReturn([
            'code' => 200, 'liste' => [['nom_projet' => 'App', 'code' => 200]],
        ]);

        $response = $this->controller->traitementInformation($this->jsonRequest(['traitement_id' => 'abc']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(3, $data['map']['nombre_ok']);
        $this->assertSame(1, $data['map']['nombre_ko']);
        $this->assertSame(5, $data['map']['nombre_projet']);
        $this->assertCount(1, $data['map']['projets']);
    }

    /* ============ traitementJournal ============ */

    public function testJournalReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->traitementJournal($this->jsonRequest(['job' => 1, 'nom_projet' => 'X']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testJournalReturns400WhenFieldsMissing(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->traitementJournal($this->jsonRequest(['job' => 1])); // missing nom_projet
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testJournalReturnsErrorWhenRepoFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchJournalRepo->expects($this->once())
            ->method('selectBatchExecutionJournalByJob')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);

        $response = $this->controller->traitementJournal($this->jsonRequest(['job' => 1, 'nom_projet' => 'X']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('boom', $data['trace']);
    }

    public function testJournalReturns204WhenStreamIsMissing(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchJournalRepo->method('selectBatchExecutionJournalByJob')->willReturn([
            'code' => 200, 'journal' => [['compte_rendu' => null]],
        ]);

        $response = $this->controller->traitementJournal($this->jsonRequest(['job' => 1, 'nom_projet' => 'X']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(204, $data['code']);
    }

    public function testJournalReturns204WhenContentIsEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $stream = fopen('php://temp', 'r+');
        // stream is empty
        rewind($stream);

        $this->batchJournalRepo->method('selectBatchExecutionJournalByJob')->willReturn([
            'code' => 200, 'journal' => [['compte_rendu' => $stream]],
        ]);

        $response = $this->controller->traitementJournal($this->jsonRequest(['job' => 1, 'nom_projet' => 'X']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(204, $data['code']);
    }

    public function testJournalReturnsHtmlFromGzipStream(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $html = '<div>Hello world</div>';
        $gzip = gzencode($html);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $gzip);
        rewind($stream);

        $this->batchJournalRepo->method('selectBatchExecutionJournalByJob')->willReturn([
            'code' => 200, 'journal' => [['compte_rendu' => $stream]],
        ]);

        $response = $this->controller->traitementJournal($this->jsonRequest(['job' => 1, 'nom_projet' => 'X']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame($html, $data['html']);
    }

    public function testJournalReturnsHtmlFromPlainStream(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $html = '<div>plain content</div>';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $html);
        rewind($stream);

        $this->batchJournalRepo->method('selectBatchExecutionJournalByJob')->willReturn([
            'code' => 200, 'journal' => [['compte_rendu' => $stream]],
        ]);

        $response = $this->controller->traitementJournal($this->jsonRequest(['job' => 1, 'nom_projet' => 'X']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame($html, $data['html']);
    }

    /* ============ traitementSuivi ============ */

    public function testTraitementSuiviFlashesWarningWithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('batch/index.html.twig', $this->anything())
            ->willReturn('<html>no-role</html>');

        $response = $this->controller->traitementSuivi();

        $this->assertSame('<html>no-role</html>', $response->getContent());
    }

    public function testTraitementSuiviFlashesErrorWhenRepoFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->expects($this->once())
            ->method('selectBatchTraitementActivated')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>err</html>');

        $this->controller->traitementSuivi();
    }

    public function testTraitementSuiviFlashesWarningWhenListEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('selectBatchTraitementActivated')->willReturn([
            'code' => 200, 'liste' => [],
        ]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>empty</html>');

        $this->controller->traitementSuivi();
    }

    public function testTraitementSuiviHappyPathBuildsTraitementsTable(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('selectBatchTraitementActivated')->willReturn([
            'code' => 200,
            'liste' => [
                [
                    'debut' => '2026-04-10 10:00:00',
                    'fin' => '2026-04-10 10:30:00',
                    'success' => 1,
                    'mode_collecte' => 'TRAITEMENT AUTOMATIQUE',
                    'titre' => 'T1',
                    'portefeuille' => 'P',
                    'projet' => 5,
                    'responsable_short' => 'Alice Dupont',
                    'traitement_id' => 'abc',
                ],
                [
                    'debut' => '',  // pas encore démarré
                    'fin' => null,
                    'success' => null,
                    'mode_collecte' => 'TRAITEMENT MANUEL',
                    'titre' => 'T2',
                    'portefeuille' => 'P',
                    'projet' => 2,
                    'responsable_short' => 'Bob',
                    'traitement_id' => 'def',
                ],
            ],
        ]);

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('batch/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->controller->traitementSuivi();

        $this->assertCount(2, $capturedCtx['traitements']);
        $this->assertSame('Succès', $capturedCtx['traitements'][0]['message']);
        $this->assertSame('automatique', $capturedCtx['traitements'][0]['type']);
        $this->assertSame('---', $capturedCtx['traitements'][1]['message']);
        $this->assertSame('manuel', $capturedCtx['traitements'][1]['type']);
    }

    /* ============ rapports ============ */

    public function testRapportsRendersStatsFromTraitements(): void
    {
        // 2 traitements : un avec code 200 et 202, un avec code 500
        $collecte1 = $this->makeJournalMock(200);
        $collecte2 = $this->makeJournalMock(202);
        $collecte3 = $this->makeJournalMock(500);

        $traitement1 = $this->createMock(BatchExecution::class);
        $traitement1->method('getCollectes')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$collecte1, $collecte2]));

        $traitement2 = $this->createMock(BatchExecution::class);
        $traitement2->method('getCollectes')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$collecte3]));

        $this->batchExecutionRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['dateEnregistrement' => 'DESC'])
            ->willReturn([$traitement1, $traitement2]);

        // createQueryBuilder fluent chain returning a list of users
        $query = $this->getMockBuilder(\Doctrine\ORM\Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSingleColumnResult'])
            ->getMock();
        $query->method('getSingleColumnResult')->willReturn(['alice@x', 'bob@x']);

        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->batchExecutionRepo->method('createQueryBuilder')->willReturn($qb);

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('batch/rapport.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>rapport</html>');

        $this->controller->rapports();

        $this->assertSame(2, $capturedCtx['total']);
        $this->assertSame(1, $capturedCtx['nombre_success']);
        $this->assertSame(1, $capturedCtx['nombre_bypass']);
        $this->assertSame(1, $capturedCtx['nombre_erreur']);
        $this->assertSame(['alice@x', 'bob@x'], $capturedCtx['users']);
    }

    /* ============ rapportExecutionPdf ============ */

    public function testRapportExecutionPdfThrowsNotFoundWhenBatchMissing(): void
    {
        $this->batchExecutionRepo->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller->rapportExecutionPdf('unknown-token');
    }

    public function testRapportExecutionPdfReturnsPdfInline(): void
    {
        $batch = $this->createMock(BatchExecution::class);
        $this->batchExecutionRepo->method('findBy')->willReturn([$batch]);

        $this->pdfExportService->expects($this->once())
            ->method('generateRapportPdf')
            ->with($batch, 'Document Interne')
            ->willReturn('%PDF-1.4-fake');

        $response = $this->controller->rapportExecutionPdf('token-abc');

        $this->assertSame('%PDF-1.4-fake', $response->getContent());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
    }

    public function testRapportExecutionPdfReturnsPdfAsAttachmentWhenDownload(): void
    {
        $batch = $this->createMock(BatchExecution::class);
        $this->batchExecutionRepo->method('findBy')->willReturn([$batch]);
        $this->pdfExportService->method('generateRapportPdf')->willReturn('%PDF-fake');

        $response = $this->controller->rapportExecutionPdf('token-abc', true);

        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
    }

    /* ============ helpers ============ */

    /**
     * @return BatchExecutionJournal&MockObject
     */
    private function makeJournalMock(int $code): BatchExecutionJournal
    {
        $j = $this->createMock(BatchExecutionJournal::class);
        $j->method('getCode')->willReturn($code);
        return $j;
    }

    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT);
        return new Request([], [], [], [], [], [], $content);
    }
}
