<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteMesureController;
use App\Entity\Mesures;
use App\Repository\MesuresRepository;
use App\Service\{ClientService, UrlBuilderService};
use App\Service\CommandRebuildHistorique\BuildMapHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteMesureControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL = 'https://sonar.example.com/api/...';

    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var ClientService&MockObject */           private MockObject $client;
    /** @var UrlBuilderService&MockObject */       private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */         private MockObject $logger;
    /** @var BuildMapHistoryService&MockObject */  private MockObject $buildMap;
    /** @var MesuresRepository&MockObject */       private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */   private MockObject $parameterBag;

    private BatchCollecteMesureController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->buildMap = $this->createMock(BuildMapHistoryService::class);
        $this->repo = $this->createMock(MesuresRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(Mesures::class)
            ->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);

        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
        ]);

        // metricsKey : stub souple (appelé par tout test qui passe la 1re Sonar OK)
        $this->buildMap->method('metricsKey')->willReturn('bugs,coverage,ncloc');
        // metricsRebuild : stub par défaut qu'on peut surcharger par test
        $this->buildMap->method('metricsRebuild')->willReturn(['rebuilt' => true]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->parameterBag],
        ]);

        $this->controller = new BatchCollecteMesureController(
            $this->em,
            $this->client,
            $this->urlBuilder,
            $this->logger,
            $this->buildMap
        );
        $this->controller->setContainer($container);
    }

    public function testBatchCollecteMesureReturnsErrorWhenFirstSonarCallFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'erreur' => 'sonar down']);

        $this->repo->expects($this->never())->method('deleteMesuresMavenKey');
        $this->repo->expects($this->never())->method('insertMesures');

        $result = $this->controller->BatchCollecteMesure(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 503, 'erreur' => 'sonar down'], $result);
    }

    public function testBatchCollecteMesureReturnsErrorWhenDeleteFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['projectName' => 'App']]);

        $this->repo->expects($this->once())
            ->method('deleteMesuresMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->repo->expects($this->never())->method('insertMesures');

        $result = $this->controller->BatchCollecteMesure(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'delete failed'], $result);
    }

    public function testBatchCollecteMesureReturnsErrorWhenInsertFails(): void
    {
        // 1er call Sonar OK, 2ème call Sonar OK avec measures → passe au delete+insert
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['projectName' => 'App']],
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'bugs', 'value' => '2'],
                ]]]]
            );

        $this->repo->method('deleteMesuresMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertMesures')
            ->willReturn(['code' => 500, 'erreur' => 'insert boom']);

        $result = $this->controller->BatchCollecteMesure(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'insert boom'], $result);
    }

    public function testBatchCollecteMesureHappyPathReturnsDataAndHistorique(): void
    {
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['projectName' => 'MyApp']],
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'bugs', 'value' => '2'],
                    ['metric' => 'coverage', 'value' => '87'],
                    ['metric' => 'ncloc'],                       // valeur manquante → 0
                ]]]]
            );

        $this->repo->method('deleteMesuresMavenKey')->willReturn(['code' => 200]);

        // On capture la map passée à metricsRebuild pour vérifier la parsing
        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedMetrics = [];
        $this->buildMap->method('metricsRebuild')
            ->willReturnCallback(function (array $measures) use (&$capturedMetrics) {
                $capturedMetrics = $measures;
                return ['rebuilt' => true];
            });

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedInsert = [];
        $this->repo->expects($this->once())
            ->method('insertMesures')
            ->with($this->callback(function (array $data) use (&$capturedInsert) {
                $capturedInsert = $data;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteMesure(self::MAVEN_KEY, 'auto', 'batch@x');

        // Mesures parsed depuis Sonar + default 0 pour valeur manquante
        $this->assertSame('2', $capturedMetrics['bugs']);
        $this->assertSame('87', $capturedMetrics['coverage']);
        $this->assertSame(0, $capturedMetrics['ncloc']); // fallback ?? 0

        // Map persistée : merge rebuilt + metadata
        $this->assertSame(self::MAVEN_KEY, $capturedInsert['maven_key']);
        $this->assertSame('myapp', $capturedInsert['project_name']); // strtolower
        $this->assertSame('auto', $capturedInsert['mode_collecte']);
        $this->assertSame('batch@x', $capturedInsert['utilisateur_collecte']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $capturedInsert['date_enregistrement']);
        $this->assertTrue($capturedInsert['rebuilt']); // hérité de metricsRebuild

        $this->assertSame(200, $result['code']);
        $this->assertSame($capturedInsert, $result['data']);
        $this->assertSame($capturedInsert, $result['historique']);
    }

    public function testBatchCollecteMesureFallsBackToInconnuWhenProjectNameMissing(): void
    {
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => []], // pas de projectName
                ['code' => 200, 'json' => ['component' => ['measures' => []]]]
            );

        $this->repo->method('deleteMesuresMavenKey')->willReturn(['code' => 200]);

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $captured = [];
        $this->repo->expects($this->once())
            ->method('insertMesures')
            ->with($this->callback(function (array $data) use (&$captured) {
                $captured = $data;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->BatchCollecteMesure(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame('inconnu', $captured['project_name']);
    }
}
