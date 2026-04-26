<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteHotspotDetailController;
use App\Entity\HotspotDetails;
use App\Entity\Hotspots;
use App\Entity\InformationProjet;
use App\Repository\HotspotDetailsRepository;
use App\Repository\HotspotsRepository;
use App\Repository\InformationProjetRepository;
use App\Service\ClientService;
use App\Service\UrlBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteHotspotDetailControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */      private MockObject $em;
    /** @var ClientService&MockObject */               private MockObject $client;
    /** @var UrlBuilderService&MockObject */           private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */             private MockObject $logger;
    /** @var HotspotsRepository&MockObject */          private MockObject $hotspotsRepo;
    /** @var HotspotDetailsRepository&MockObject */    private MockObject $hotspotDetailsRepo;
    /** @var InformationProjetRepository&MockObject */ private MockObject $informationRepo;
    /** @var ParameterBagInterface&MockObject */       private MockObject $parameterBag;

    private BatchCollecteHotspotDetailController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->hotspotsRepo = $this->createMock(HotspotsRepository::class);
        $this->hotspotDetailsRepo = $this->createMock(HotspotDetailsRepository::class);
        $this->informationRepo = $this->createMock(InformationProjetRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [Hotspots::class, $this->hotspotsRepo],
            [HotspotDetails::class, $this->hotspotDetailsRepo],
            [InformationProjet::class, $this->informationRepo],
        ]);

        $this->urlBuilder->method('build')->willReturn('https://sonar/api/hotspots/show?...');

        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['module.frontend', 'presentation,webapp,front'],
            ['module.backend', 'back,controller,api'],
            ['module.autre', 'batch,rdd'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteHotspotDetailController(
            $this->em, $this->client, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    /* ---------------- hotspotDetail ---------------- */

    public function testHotspotDetailReturnsErrorWhenSonarCallFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'server down']);

        $r = $this->controller->hotspotDetail(self::MAVEN_KEY, 'HS1');

        $this->assertSame(500, $r['code']);
        $this->assertSame('server down', $r['erreur']);
    }

    public function testHotspotDetailReturnsNCWhenNoJsonInResult(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200]); // no 'json' key

        $r = $this->controller->hotspotDetail(self::MAVEN_KEY, 'HS1');

        $this->assertSame('NC', $r['security_category']);
        $this->assertSame('NC', $r['severity']);
        $this->assertSame('NC', $r['file_name']);
        $this->assertSame('NC', $r['hotspot_key']);
        $this->assertSame('NC', $r['status']);
    }

    public function testHotspotDetailParsesFrontendPath(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'key' => 'HS1',
                'rule' => ['vulnerabilityProbability' => 'HIGH', 'securityCategory' => 'sql-injection', 'key' => 'phpsec:S1', 'name' => 'SQL'],
                'component' => ['path' => 'presentation/Home.html', 'name' => 'Home.html', 'key' => self::MAVEN_KEY . ':presentation/Home.html'],
                'status' => 'TO_REVIEW',
                'message' => 'check this',
                'line' => 42,
            ],
        ]);

        $r = $this->controller->hotspotDetail(self::MAVEN_KEY, 'HS1');

        $this->assertSame('HIGH', $r['severity']);
        $this->assertSame(1, $r['niveau']);
        $this->assertSame(1, $r['frontend']);
        $this->assertSame(0, $r['backend']);
        $this->assertSame(0, $r['autre']);
        $this->assertSame(0, $r['inconnu']);
        $this->assertSame('Home.html', $r['file_name']);
        $this->assertSame(42, $r['line']);
    }

    public function testHotspotDetailParsesBackendPathFromKeyFallback(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'key' => 'HS2',
                'rule' => ['vulnerabilityProbability' => 'LOW', 'securityCategory' => 'xss', 'key' => 'phpsec:S2', 'name' => 'XSS'],
                // no 'path' → falls to 'key' branch
                'component' => ['name' => 'Main.java', 'key' => self::MAVEN_KEY . ':controller/Main.java'],
                'status' => 'REVIEWED',
                'resolution' => 'FIXED',
                'message' => 'check',
            ],
        ]);

        $r = $this->controller->hotspotDetail(self::MAVEN_KEY, 'HS2');

        $this->assertSame('LOW', $r['severity']);
        $this->assertSame(3, $r['niveau']);
        $this->assertSame(0, $r['frontend']);
        $this->assertSame(1, $r['backend']);
        $this->assertSame(0, $r['line']); // missing line → 0
        $this->assertSame('FIXED', $r['resolution']);
        $this->assertSame('controller/Main.java', $r['file_path']);
    }

    public function testHotspotDetailDefaultsSeverityToMediumWhenEmpty(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'key' => 'HS3',
                'rule' => ['vulnerabilityProbability' => '', 'securityCategory' => 'x', 'key' => 'k', 'name' => 'n'],
                'component' => ['path' => 'unknown/X.txt', 'name' => 'X.txt', 'key' => self::MAVEN_KEY . ':unknown/X.txt'],
                'status' => 'TO_REVIEW',
                'message' => 'x',
            ],
        ]);

        $r = $this->controller->hotspotDetail(self::MAVEN_KEY, 'HS3');

        $this->assertSame('MEDIUM', $r['severity']);
        $this->assertSame(2, $r['niveau']);
        $this->assertSame(1, $r['inconnu']); // matches none of frontend/backend/autre
    }

    /* ---------------- batchCollecteHotspotDetail ---------------- */

    public function testBatchCollecteReturnsErrorWhenInformationRepoFails(): void
    {
        $this->informationRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $r['code']);
    }

    public function testBatchCollecteReturns404WhenNoInformation(): void
    {
        $this->informationRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => []]);

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(404, $r['code']);
    }

    public function testBatchCollecteReturnsErrorWhenSelectHotspotsFails(): void
    {
        $this->informationRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200,
            'info' => [['date' => '2026-04-10 10:00:00', 'version' => '1.0']],
        ]);
        $this->hotspotsRepo->expects($this->once())
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 500, 'erreur' => 'fail']);

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $r['code']);
    }

    public function testBatchCollecteReturnsErrorWhenDeleteFails(): void
    {
        $this->informationRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200,
            'info' => [['date' => '2026-04-10 10:00:00', 'version' => '1.0']],
        ]);
        $this->hotspotsRepo->method('selectHotspotsToReview')->willReturn([
            'code' => 200,
            'liste' => [['hotspot_key' => 'HS1']],
        ]);
        $this->hotspotDetailsRepo->expects($this->once())
            ->method('deleteHotspotDetailsMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete fail']);

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $r['code']);
        $this->assertSame('delete fail', $r['erreur']);
    }

    public function testBatchCollecteReturnsEmptyHistoriqueWhenListIsEmpty(): void
    {
        $this->informationRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200,
            'info' => [['date' => '2026-04-10 10:00:00', 'version' => '1.0']],
        ]);
        $this->hotspotsRepo->method('selectHotspotsToReview')->willReturn([
            'code' => 200, 'liste' => [],
        ]);
        $this->hotspotDetailsRepo->method('deleteHotspotDetailsMavenKey')->willReturn(['code' => 200]);

        $this->hotspotDetailsRepo->expects($this->never())->method('insertHotspotDetails');

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $r['code']);
        $this->assertSame(0, $r['nombre']);
        $this->assertSame(0, $r['historique']['menace_potentielle_totale']);
    }

    public function testBatchCollecteHappyPathBuildsHistoriqueFromNiveauxReviewed(): void
    {
        $this->informationRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200,
            'info' => [['date' => '2026-04-10 10:00:00', 'version' => '2.0']],
        ]);
        $this->hotspotsRepo->method('selectHotspotsToReview')->willReturn([
            'code' => 200,
            'liste' => [
                ['hotspot_key' => 'HS1'],
                ['hotspot_key' => 'HS2'],
            ],
        ]);
        $this->hotspotDetailsRepo->method('deleteHotspotDetailsMavenKey')->willReturn(['code' => 200]);

        // hotspotDetail() needs the client for each hotspot
        $hsJson = [
            'code' => 200,
            'json' => [
                'key' => 'HS1',
                'rule' => ['vulnerabilityProbability' => 'HIGH', 'securityCategory' => 'x', 'key' => 'k', 'name' => 'n'],
                'component' => ['path' => 'back/Main.java', 'name' => 'Main.java', 'key' => self::MAVEN_KEY . ':back/Main.java'],
                'status' => 'TO_REVIEW',
                'message' => 'm',
                'line' => 10,
            ],
        ];
        $this->client->method('httpSonarQube')->willReturn($hsJson);

        $capturedInsert = null;
        $this->hotspotDetailsRepo->expects($this->once())
            ->method('insertHotspotDetails')
            ->with($this->callback(function (array $maps) use (&$capturedInsert) {
                $capturedInsert = $maps;
                return true;
            }))
            ->willReturn(['code' => 200]);

        // selectHotspotsByNiveau called twice (TO_REVIEW then REVIEWED)
        $this->hotspotsRepo->method('selectHotspotsByNiveau')->willReturnOnConsecutiveCalls(
            // TO_REVIEW
            ['code' => 200, 'liste' => [
                ['niveau' => '1', 'hotspot' => 3],
                ['niveau' => '2', 'hotspot' => 2],
                ['niveau' => '3', 'hotspot' => 1],
            ]],
            // REVIEWED
            ['code' => 200, 'liste' => [
                ['niveau' => '1', 'hotspot' => 5],
                ['niveau' => '2', 'hotspot' => 0],
                ['niveau' => '3', 'hotspot' => 0],
            ]],
        );

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $r['code']);
        $this->assertSame(2, $r['nombre']);
        $this->assertCount(2, $capturedInsert);
        $this->assertSame(self::MAVEN_KEY, $capturedInsert[0]['maven_key']);
        $this->assertSame('HS1', $capturedInsert[0]['hotspot_key']);
        $this->assertSame(6, $r['historique']['menace_potentielle_totale']); // 3+2+1
        $this->assertSame(3, $r['historique']['menace_potentielle_to_review_high']);
        $this->assertSame(5, $r['historique']['menace_potentielle_reviewed_high']);
    }

    public function testBatchCollecteReturnsAlertWhenToReviewNiveauxFails(): void
    {
        $this->informationRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200,
            'info' => [['date' => '2026-04-10 10:00:00', 'version' => '2.0']],
        ]);
        $this->hotspotsRepo->method('selectHotspotsToReview')->willReturn([
            'code' => 200, 'liste' => [['hotspot_key' => 'HS1']],
        ]);
        $this->hotspotDetailsRepo->method('deleteHotspotDetailsMavenKey')->willReturn(['code' => 200]);

        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => [
            'key' => 'HS1',
            'rule' => ['vulnerabilityProbability' => 'HIGH', 'securityCategory' => 'x', 'key' => 'k', 'name' => 'n'],
            'component' => ['path' => 'back/x', 'name' => 'x', 'key' => self::MAVEN_KEY . ':back/x'],
            'status' => 'TO_REVIEW', 'message' => 'm',
        ]]);
        $this->hotspotDetailsRepo->method('insertHotspotDetails')->willReturn(['code' => 200]);

        $this->hotspotsRepo->method('selectHotspotsByNiveau')->willReturn([
            'code' => 500, 'erreur' => 'niveau fail',
        ]);

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $r['code']);
        $this->assertSame('alert', $r['type']);
        $this->assertSame('TO_REVIEW', $r['test']);
    }

    public function testBatchCollecteReturnsErrorWhenInsertFails(): void
    {
        $this->informationRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200, 'info' => [['date' => '2026-04-10 10:00:00', 'version' => '1.0']],
        ]);
        $this->hotspotsRepo->method('selectHotspotsToReview')->willReturn([
            'code' => 200, 'liste' => [['hotspot_key' => 'HS1']],
        ]);
        $this->hotspotDetailsRepo->method('deleteHotspotDetailsMavenKey')->willReturn(['code' => 200]);
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => [
            'key' => 'HS1',
            'rule' => ['vulnerabilityProbability' => 'HIGH', 'securityCategory' => 'x', 'key' => 'k', 'name' => 'n'],
            'component' => ['path' => 'back/x', 'name' => 'x', 'key' => self::MAVEN_KEY . ':back/x'],
            'status' => 'TO_REVIEW', 'message' => 'm',
        ]]);

        $this->hotspotDetailsRepo->expects($this->once())
            ->method('insertHotspotDetails')
            ->willReturn(['code' => 500, 'erreur' => 'insert fail']);

        $r = $this->controller->batchCollecteHotspotDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $r['code']);
        $this->assertSame('insert fail', $r['erreur']);
    }
}
