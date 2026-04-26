<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteRepartitionController;
use App\Entity\Repartition;
use App\Entity\RepartitionTemp;
use App\Repository\RepartitionRepository;
use App\Repository\RepartitionTempRepository;
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
class BatchCollecteRepartitionControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';
    private const SETUP = 'setup-1';
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL = 'https://sonar.example.com/api/issues/search?...';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var RepartitionRepository&MockObject */
    private MockObject $repartRepo;

    /** @var RepartitionTempRepository&MockObject */
    private MockObject $tempRepo;

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    private BatchCollecteRepartitionController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repartRepo = $this->createMock(RepartitionRepository::class);
        $this->tempRepo = $this->createMock(RepartitionTempRepository::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [Repartition::class, $this->repartRepo],
            [RepartitionTemp::class, $this->tempRepo],
        ]);

        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);

        // Paramètres standards (sonar.url + mots-clés modules) — utilisés par getParameter
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['module.frontend', 'presentation,webapp,front,frontend,angular'],
            ['module.backend', 'back,backend,controller,api,service'],
            ['module.autre', 'batch,rdd,etl,pipeline'],
        ]);

        // Container mock (AbstractController::getParameter)
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['parameter_bag', true],
            ['serializer', false],
        ]);
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->parameterBag],
        ]);

        $this->controller = new BatchCollecteRepartitionController(
            $this->em,
            $this->client,
            $this->urlBuilder,
            $this->logger
        );
        $this->controller->setContainer($container);
    }

    // ═════════════ collecteRepartitionModule ═══════════════════════════════

    public function testCollecteRepartitionModuleAggregatesSeverityCountsFromFacets(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => ['facets' => [[
                    'values' => [
                        ['val' => 'INFO',     'count' => 1],
                        ['val' => 'MINOR',    'count' => 2],
                        ['val' => 'MAJOR',    'count' => 3],
                        ['val' => 'CRITICAL', 'count' => 4],
                        ['val' => 'BLOCKER',  'count' => 5],
                    ],
                ]]],
            ]);

        $result = $this->controller->collecteRepartitionModule(self::MAVEN_KEY, 'BUG');

        $this->assertSame(200, $result['code']);
        $this->assertSame('BUG', $result['category']);
        $this->assertSame(15, $result['total']);
        $this->assertSame(1, $result['info']);
        $this->assertSame(5, $result['blocker']);
    }

    public function testCollecteRepartitionModulePropagatesHttpError(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'Sonar down']);

        $result = $this->controller->collecteRepartitionModule(self::MAVEN_KEY, 'BUG');

        $this->assertSame(500, $result['code']);
        $this->assertSame('Sonar down', $result['erreur']);
    }

    // ═════════════ batchCollecteRepartition ════════════════════════════════

    public function testBatchCollecteRepartitionReturns201WhenDataAlreadyExists(): void
    {
        $this->tempRepo->expects($this->once())
            ->method('checkExistData')
            ->with([
                'maven_key' => self::MAVEN_KEY,
                'category' => 'BUG',
                'severity' => 'BLOCKER',
                'setup' => self::SETUP,
            ])
            ->willReturn(['total' => 5]);

        $this->client->expects($this->never())->method('httpSonarQube');

        $result = $this->controller->batchCollecteRepartition(self::MAVEN_KEY, 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(201, $result['code']);
        $this->assertSame('primary', $result['type']);
    }

    public function testBatchCollecteRepartitionReturns200WithZeroTotalWhenNoIssues(): void
    {
        $this->tempRepo->method('checkExistData')->willReturn(['total' => 0]);
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['total' => 0, 'issues' => []]]);

        $this->tempRepo->expects($this->never())->method('deleteOldRecords');
        $this->tempRepo->expects($this->never())->method('batchInsertIssuesSQL');

        $result = $this->controller->batchCollecteRepartition(self::MAVEN_KEY, 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['data']['total']);
        $this->assertSame('BUG', $result['data']['category']);
    }

    public function testBatchCollecteRepartitionReturnsErrorWhenDeleteOldRecordsFails(): void
    {
        $this->tempRepo->method('checkExistData')->willReturn(['total' => 0]);
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => [
                    'total' => 1,
                    'issues' => [['component' => 'c', 'severity' => 'BLOCKER']],
                ],
            ]);

        $this->tempRepo->expects($this->once())
            ->method('deleteOldRecords')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->tempRepo->expects($this->never())->method('batchInsertIssuesSQL');

        $result = $this->controller->batchCollecteRepartition(self::MAVEN_KEY, 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(500, $result['code']);
        $this->assertSame('alert', $result['type']);
    }

    public function testBatchCollecteRepartitionHappyPathPersistsAllPages(): void
    {
        $this->tempRepo->method('checkExistData')->willReturn(['total' => 0]);

        // Première page pour pré-check + batch pages 1..N ensuite
        $issue = ['component' => 'com.acme:app:src/X.java', 'severity' => 'BLOCKER'];
        $this->client->expects($this->exactly(3)) // pré-check + page 1 + page 2 (vide)
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['total' => 2, 'issues' => [$issue]]], // pré-check
                ['code' => 200, 'json' => ['total' => 2, 'issues' => [$issue, $issue]]], // page 1
                ['code' => 200, 'json' => ['total' => 2, 'issues' => []]], // page 2 → break
            );

        $this->tempRepo->expects($this->once())
            ->method('deleteOldRecords')
            ->willReturn(['code' => 200]);

        $this->tempRepo->expects($this->once())
            ->method('batchInsertIssuesSQL')
            ->with($this->callback(fn (array $issues) => count($issues) === 2))
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteRepartition(self::MAVEN_KEY, 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(200, $result['code']);
        $this->assertSame(2, $result['data']['total']);
    }

    // ═════════════ batchCollecteRepartitionAnalyse ═════════════════════════

    public function testBatchCollecteRepartitionAnalyseReturns400WhenMavenKeyIsNC(): void
    {
        $this->tempRepo->expects($this->never())->method('selectRepartitionByTypeAndSeverity');

        $result = $this->controller->batchCollecteRepartitionAnalyse('N.C', 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(400, $result['code']);
        $this->assertSame('alert', $result['type']);
    }

    public function testBatchCollecteRepartitionAnalysePropagatesRepositoryError(): void
    {
        $this->tempRepo->expects($this->once())
            ->method('selectRepartitionByTypeAndSeverity')
            ->willReturn(['code' => 503, 'erreur' => 'timeout']);

        $result = $this->controller->batchCollecteRepartitionAnalyse(self::MAVEN_KEY, 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(503, $result['code']);
        $this->assertSame('timeout', $result['trace']);
    }

    public function testBatchCollecteRepartitionAnalyseClassifiesIssuesByModuleKeyword(): void
    {
        // composants aux paths qui matchent frontend/backend/autre/inconnu
        $issues = [
            ['component' => 'com.acme:app:presentation/views/home.html'],        // frontend
            ['component' => 'com.acme:app:webapp/src/main/app.ts'],              // frontend
            ['component' => 'com.acme:app:api/rest/Controller.java'],            // backend (backend keywords include 'api')
            ['component' => 'com.acme:app:batch/nightly.sh'],                    // autre
            ['component' => 'com.acme:app:unclassified/readme.md'],              // inconnu
        ];

        $this->tempRepo->expects($this->once())
            ->method('selectRepartitionByTypeAndSeverity')
            ->willReturn(['code' => 200, 'liste' => $issues]);

        $result = $this->controller->batchCollecteRepartitionAnalyse(self::MAVEN_KEY, 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(200, $result['code']);
        $this->assertSame(2, $result['frontend']);
        $this->assertSame(1, $result['backend']);
        $this->assertSame(1, $result['autre']);
        $this->assertSame(1, $result['inconnu']);
        $this->assertSame(5, $result['total']);
    }

    public function testBatchCollecteRepartitionAnalyseReturnsZeroesWhenIssuesEmpty(): void
    {
        $this->tempRepo->expects($this->once())
            ->method('selectRepartitionByTypeAndSeverity')
            ->willReturn(['code' => 200, 'liste' => []]);

        $result = $this->controller->batchCollecteRepartitionAnalyse(self::MAVEN_KEY, 'BUG', 'BLOCKER', self::SETUP);

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['frontend']);
    }

    // ═════════════ batchCollecteRepartitionMaJ ═════════════════════════════

    public function testBatchCollecteRepartitionMaJUpdatesWithCompleteStatusWhenAllCategoriesPresent(): void
    {
        // 5 sévérités × 3 catégories = 15 lignes, chacune avec [cat, sev, frontend, backend, autre, inconnu]
        $calcul = [];
        foreach (['BUG', 'VULNERABILITY', 'CODE_SMELL'] as $cat) {
            foreach (['BLOCKER', 'CRITICAL', 'MAJOR', 'MINOR', 'INFO'] as $sev) {
                $calcul[] = [$cat, $sev, 1, 2, 3, 4];
            }
        }

        $this->repartRepo->expects($this->once())
            ->method('updateRepartition')
            ->with($this->callback(function (array $map) {
                return $map['control'] === 'complet (100%)'
                    && $map['frontend'] === 15
                    && $map['backend'] === 30
                    && $map['autre'] === 45
                    && $map['inconnu'] === 60
                    && $map['setup'] === self::SETUP
                    && array_key_exists('frontend_bug_blocker', $map)
                    && array_key_exists('inconnu_code_smell_info', $map);
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteRepartitionMaJ(self::MAVEN_KEY, $calcul, self::SETUP);

        $this->assertSame(200, $result['code']);
    }

    public function testBatchCollecteRepartitionMaJMarksPartialWhenOneCategoryIncomplete(): void
    {
        // BUG complet (5), VULNERABILITY complet (5), CODE_SMELL incomplet (2 seulement)
        $calcul = [];
        foreach (['BUG', 'VULNERABILITY'] as $cat) {
            foreach (['BLOCKER', 'CRITICAL', 'MAJOR', 'MINOR', 'INFO'] as $sev) {
                $calcul[] = [$cat, $sev, 1, 0, 0, 0];
            }
        }
        $calcul[] = ['CODE_SMELL', 'BLOCKER', 1, 0, 0, 0];
        $calcul[] = ['CODE_SMELL', 'CRITICAL', 1, 0, 0, 0];

        $this->repartRepo->expects($this->once())
            ->method('updateRepartition')
            ->with($this->callback(function (array $map) {
                // 1 set manquant → 'partiel (66%)'
                return $map['control'] === 'partiel (66%)'
                    // CODE_SMELL incomplet → champs à -1
                    && $map['frontend_code_smell_blocker'] === -1
                    && $map['frontend_code_smell_info'] === -1;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteRepartitionMaJ(self::MAVEN_KEY, $calcul, self::SETUP);

        $this->assertSame(200, $result['code']);
    }

    public function testBatchCollecteRepartitionMaJReturnsErrorWhenUpdateFails(): void
    {
        $this->repartRepo->expects($this->once())
            ->method('updateRepartition')
            ->willReturn(['code' => 500, 'erreur' => 'update failed']);

        $result = $this->controller->batchCollecteRepartitionMaJ(self::MAVEN_KEY, [], self::SETUP);

        $this->assertSame(500, $result['code']);
        $this->assertSame('update failed', $result['erreur']);
    }
}
