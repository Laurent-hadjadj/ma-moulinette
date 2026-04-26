<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Entity\HotspotOwasp;
use App\Entity\InformationProjet;
use App\Repository\HotspotOwaspRepository;
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
class BatchCollecteHotspotOwaspControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */           private MockObject $em;
    /** @var ClientService&MockObject */                    private MockObject $client;
    /** @var UrlBuilderService&MockObject */                private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */                  private MockObject $logger;
    /** @var HotspotOwaspRepository&MockObject */           private MockObject $hotspotRepo;
    /** @var InformationProjetRepository&MockObject */      private MockObject $infoRepo;
    /** @var ParameterBagInterface&MockObject */            private MockObject $parameterBag;

    private BatchCollecteHotspotOwaspController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->hotspotRepo = $this->createMock(HotspotOwaspRepository::class);
        $this->infoRepo = $this->createMock(InformationProjetRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [HotspotOwasp::class, $this->hotspotRepo],
            [InformationProjet::class, $this->infoRepo],
        ]);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/...');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteHotspotOwaspController(
            $this->em, $this->client, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testMenaceA0WipesHotspotsAndReturnsEarlyForSonar8(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '8'],
        ]);

        $this->hotspotRepo->expects($this->once())
            ->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        $this->client->expects($this->never())->method('httpSonarQube');
        $this->infoRepo->expects($this->never())->method('selectInformationProjetVersion');

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a0');

        $this->assertSame(200, $result['code']);
        $this->assertSame('effacement', $result['info']);
        $this->assertArrayHasKey('owasp_2017', $result);
        $this->assertArrayNotHasKey('owasp_2021', $result); // v8 → pas de 2021
    }

    public function testMenaceA0ReturnsOwasp2021KeyForSonarVersion10(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '10'],
        ]);

        $this->hotspotRepo->method('deleteHotspotOwaspMavenKey')->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a0');

        $this->assertArrayHasKey('owasp_2021', $result);
    }

    public function testMenaceA0ReturnsErrorWhenDeleteFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '8'],
        ]);

        $this->hotspotRepo->expects($this->once())
            ->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a0');

        $this->assertSame(500, $result['code']);
    }

    public function testReturnsErrorWhenInfoProjetQueryFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '10'],
        ]);

        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 503, 'erreur' => 'db']);

        $this->client->expects($this->never())->method('httpSonarQube');

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a1');

        $this->assertSame(503, $result['code']);
    }

    public function testReturns404WhenNoInfoProjet(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '10'],
        ]);

        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => []]);

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a1');

        $this->assertSame(404, $result['code']);
    }

    public function testHappyPathPersistsFromBothReferentials(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '10'],
        ]);

        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [['version' => '1.0', 'date' => '2026-04-22']]]);

        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                // OWASP 2017
                ['code' => 200, 'json' => [
                    'paging' => ['total' => 2],
                    'hotspots' => [
                        ['key' => 'h1', 'vulnerabilityProbability' => 'HIGH', 'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
                        ['key' => 'h2', 'vulnerabilityProbability' => 'LOW',  'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
                    ],
                ]],
                // OWASP 2021
                ['code' => 200, 'json' => [
                    'paging' => ['total' => 1],
                    'hotspots' => [
                        ['key' => 'h3', 'vulnerabilityProbability' => 'MEDIUM', 'securityCategory' => 'sc', 'ruleKey' => 'r', 'status' => 'TO_REVIEW'],
                    ],
                ]],
            );

        $capturedInsert = null;
        $this->hotspotRepo->expects($this->once())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function (array $list) use (&$capturedInsert) {
                $capturedInsert = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a1');

        $this->assertSame(200, $result['code']);
        $this->assertSame(2, $result['owasp_2017']);
        $this->assertSame(1, $result['owasp_2021']);
        $this->assertCount(3, $capturedInsert);
        $this->assertSame(2017, $capturedInsert[0]['referential_owasp']);
        $this->assertSame(2017, $capturedInsert[1]['referential_owasp']);
        $this->assertSame(2021, $capturedInsert[2]['referential_owasp']);
    }

    public function testSkipsOwasp2021CallWhenSonarVersionIs8(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '8'],
        ]);

        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [['version' => '1.0', 'date' => '2026-04-22']]]);

        // Un seul appel Sonar (2017 uniquement)
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['paging' => ['total' => 0], 'hotspots' => []]]);

        $this->hotspotRepo->expects($this->never())->method('insertHotspotOwasp');

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a1');

        $this->assertSame(200, $result['code']);
        $this->assertSame('NC', $result['owasp_2021']);
    }

    public function testReturnsErrorWithTypeOwasp2017WhenFirstSonarCallFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['sonar.version', '10'],
        ]);

        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [['version' => '1.0', 'date' => '2026-04-22']]]);

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'down']);

        $result = $this->controller->batchCollecteHotspotOwasp(self::MAVEN_KEY, 'auto', 'u', 'a1');

        $this->assertSame(500, $result['code']);
        $this->assertSame('owasp2017', $result['type']);
    }
}
