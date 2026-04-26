<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteHotspotController;
use App\Entity\Hotspots;
use App\Entity\InformationProjet;
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
class BatchCollecteHotspotControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var ClientService&MockObject */                   private MockObject $client;
    /** @var UrlBuilderService&MockObject */               private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */                 private MockObject $logger;
    /** @var HotspotsRepository&MockObject */              private MockObject $hotspotsRepo;
    /** @var InformationProjetRepository&MockObject */     private MockObject $infoRepo;
    /** @var ParameterBagInterface&MockObject */           private MockObject $parameterBag;

    private BatchCollecteHotspotController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->hotspotsRepo = $this->createMock(HotspotsRepository::class);
        $this->infoRepo = $this->createMock(InformationProjetRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [Hotspots::class, $this->hotspotsRepo],
            [InformationProjet::class, $this->infoRepo],
        ]);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/...');
        $this->parameterBag->method('get')->willReturn('https://sonar.example.com');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteHotspotController(
            $this->em, $this->client, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testReturnsErrorWhenInformationProjetQueryFails(): void
    {
        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $this->client->expects($this->never())->method('httpSonarQube');

        $result = $this->controller->BatchCollecteHotspot(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(['code' => 500, 'erreur' => 'db down'], $result);
    }

    public function testReturns404WhenNoProjectInfo(): void
    {
        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => []]);

        $result = $this->controller->BatchCollecteHotspot(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(404, $result['code']);
    }

    public function testReturnsErrorWhenSonarCallFails(): void
    {
        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [['version' => '1.0', 'date' => '2026-04-22']]]);

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'erreur' => 'down']);

        $this->hotspotsRepo->expects($this->never())->method('deleteHotspotsMavenKey');

        $result = $this->controller->BatchCollecteHotspot(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(503, $result['code']);
    }

    public function testCountsHotspotsByProbabilityLevel(): void
    {
        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [['version' => '1.0', 'date' => '2026-04-22']]]);

        $hotspots = [
            ['key' => 'h1', 'vulnerabilityProbability' => 'HIGH',   'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
            ['key' => 'h2', 'vulnerabilityProbability' => 'HIGH',   'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
            ['key' => 'h3', 'vulnerabilityProbability' => 'MEDIUM', 'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
            ['key' => 'h4', 'vulnerabilityProbability' => 'LOW',    'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
            ['key' => 'h5', 'vulnerabilityProbability' => 'LOW',    'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
            ['key' => 'h6', 'vulnerabilityProbability' => 'LOW',    'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
        ];

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['paging' => ['total' => count($hotspots)], 'hotspots' => $hotspots],
        ]);

        $this->hotspotsRepo->method('deleteHotspotsMavenKey')->willReturn(['code' => 200]);
        $this->hotspotsRepo->expects($this->once())
            ->method('insertHotspots')
            ->with($this->callback(fn (array $map) => count($map) === 6))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteHotspot(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(2, $result['historique']['hotspot_high']);
        $this->assertSame(1, $result['historique']['hotspot_medium']);
        $this->assertSame(3, $result['historique']['hotspot_low']);
        $this->assertSame(6, $result['historique']['nombre_hotspot']);
    }

    public function testInsertsSentinelRowWhenNoHotspotsFound(): void
    {
        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [['version' => '1.0', 'date' => '2026-04-22']]]);

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['paging' => ['total' => 0], 'hotspots' => []],
        ]);

        $this->hotspotsRepo->method('deleteHotspotsMavenKey')->willReturn(['code' => 200]);

        // Une seule ligne sentinelle 'NC' doit être insérée
        $this->hotspotsRepo->expects($this->once())
            ->method('insertHotspots')
            ->with($this->callback(function (array $map) {
                return count($map) === 1
                    && $map[0]['hotspot_key'] === 'NC'
                    && $map[0]['probability'] === 'NC'
                    && $map[0]['niveau'] === -1;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteHotspot(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['historique']['nombre_hotspot']);
    }
}
