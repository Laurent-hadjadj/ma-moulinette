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

use App\Controller\Batch\BatchCollecteAnomalieController;
use App\Entity\Anomalie;
use App\Repository\AnomalieRepository;
use App\Service\ClientService;
use App\Service\DateTools;
use App\Service\ExtractName;
use App\Service\GetSonarVersionService;
use App\Service\UrlBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteAnomalieControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';

    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var ClientService&MockObject */           private MockObject $client;
    /** @var ExtractName&MockObject */             private MockObject $extractName;
    /** @var DateTools&MockObject */               private MockObject $dateTools;
    /** @var UrlBuilderService&MockObject */       private MockObject $urlBuilder;
    /** @var GetSonarVersionService&MockObject */  private MockObject $sonarVersion;
    /** @var LoggerInterface&MockObject */         private MockObject $logger;
    /** @var AnomalieRepository&MockObject */      private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */   private MockObject $parameterBag;

    private BatchCollecteAnomalieController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->extractName = $this->createMock(ExtractName::class);
        $this->dateTools = $this->createMock(DateTools::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->sonarVersion = $this->createMock(GetSonarVersionService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(AnomalieRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(Anomalie::class)
            ->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn('https://sonar/api/...');
        $this->sonarVersion->method('componentKeyParam')->willReturn('components');
        $this->extractName->method('extractNameFromMavenKey')->willReturn('app');
        $this->dateTools->method('minutesTo')->willReturnCallback(fn ($min) => (string) $min . 'min');

        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['module.frontend', 'presentation,webapp,front'],
            ['module.backend', 'back,controller,api'],
            ['module.autre', 'batch,rdd'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteAnomalieController(
            $this->em, $this->client, $this->extractName, $this->dateTools, $this->urlBuilder,
            $this->sonarVersion, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testReturnsErrorWhenGeneralSonarCallFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'down']);

        $this->repo->expects($this->never())->method('insertAnomalie');

        $result = $this->controller->BatchCollecteAnomalie(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
        $this->assertSame('general', $result['type']);
    }

    public function testReturnsErrorWithTypeWhenBugCallFails(): void
    {
        // general OK puis BUG échoue
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['paging' => ['total' => 1], 'total' => 1, 'effortTotal' => 30, 'facets' => []]],
                ['code' => 503, 'erreur' => 'bug down']
            );

        $result = $this->controller->BatchCollecteAnomalie(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(503, $result['code']);
        $this->assertSame('BUG', $result['type']);
    }

    public function testInsertsZerosWhenGeneralTotalIsZero(): void
    {
        // general total=0 → skip tout le traitement facets/delete
        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => ['paging' => ['total' => 0], 'total' => 0, 'effortTotal' => 0, 'facets' => []],
            ]);

        $this->repo->expects($this->never())->method('deleteAnomalieMavenKey');

        /* MODIF 2026-05-07 : init [] au lieu de null — intelephense ne suit pas l'assignment by-reference dans le callback. */
        $capturedMap = [];
        $this->repo->expects($this->once())
            ->method('insertAnomalie')
            ->with($this->callback(function (array $map) use (&$capturedMap) {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $capturedMap['anomalie_total']);
        $this->assertSame(0, $capturedMap['bug']);
        $this->assertSame(0, $capturedMap['frontend']);
    }

    public function testReturnsErrorWhenDeleteFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'paging' => ['total' => 5], 'total' => 5, 'effortTotal' => 60,
                'facets' => [],
            ],
        ]);

        $this->repo->expects($this->once())
            ->method('deleteAnomalieMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $this->repo->expects($this->never())->method('insertAnomalie');

        $result = $this->controller->BatchCollecteAnomalie(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testHappyPathAggregatesFacetsAndPersistsMap(): void
    {
        // general = 10 anomalies, avec severities+types+directories
        $generalPayload = [
            'code' => 200,
            'json' => [
                'paging' => ['total' => 10],
                'total' => 10, 'effortTotal' => 120,
                'facets' => [
                    [
                        'property' => 'severities',
                        'values' => [
                            ['val' => 'BLOCKER', 'count' => 2],
                            ['val' => 'CRITICAL', 'count' => 3],
                            ['val' => 'MAJOR', 'count' => 5],
                        ],
                    ],
                    [
                        'property' => 'types',
                        'values' => [
                            ['val' => 'BUG', 'count' => 3],
                            ['val' => 'VULNERABILITY', 'count' => 2],
                            ['val' => 'CODE_SMELL', 'count' => 5],
                        ],
                    ],
                    [
                        'property' => 'directories',
                        'values' => [
                            ['val' => self::MAVEN_KEY . ':presentation/Home.html', 'count' => 2], // frontend
                            ['val' => self::MAVEN_KEY . ':controller/Main.java',   'count' => 6], // backend
                            ['val' => self::MAVEN_KEY . ':batch/Run.sh',           'count' => 1], // autre
                            ['val' => self::MAVEN_KEY . ':unknown/X.txt',          'count' => 1], // inconnu
                        ],
                    ],
                ],
            ],
        ];

        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                $generalPayload,
                ['code' => 200, 'json' => ['paging' => ['total' => 3], 'total' => 3, 'effortTotal' => 30, 'facets' => []]],
                ['code' => 200, 'json' => ['paging' => ['total' => 2], 'total' => 2, 'effortTotal' => 20, 'facets' => []]],
                ['code' => 200, 'json' => ['paging' => ['total' => 5], 'total' => 5, 'effortTotal' => 70, 'facets' => []]],
            );

        $this->repo->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);

        /* MODIF 2026-05-07 : init [] au lieu de null — intelephense ne suit pas l'assignment by-reference dans le callback. */
        $capturedMap = [];
        $this->repo->expects($this->once())
            ->method('insertAnomalie')
            ->with($this->callback(function (array $map) use (&$capturedMap) {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(10, $capturedMap['anomalie_total']);
        // Severities
        $this->assertSame(2, $capturedMap['blocker']);
        $this->assertSame(3, $capturedMap['critical']);
        $this->assertSame(5, $capturedMap['major']);
        // Types
        $this->assertSame(3, $capturedMap['bug']);
        $this->assertSame(2, $capturedMap['vulnerability']);
        $this->assertSame(5, $capturedMap['code_smell']);
        // Modules (via batchAnalyseAnomalie)
        $this->assertSame(2, $capturedMap['frontend']);
        $this->assertSame(6, $capturedMap['backend']);
        $this->assertSame(1, $capturedMap['autre']);
        $this->assertSame(1, $capturedMap['inconnu']);
        // Dette technique (valeurs issues du stub minutesTo : 'XXmin')
        $this->assertSame('120min', $capturedMap['dette']);
        $this->assertSame(120, $capturedMap['dette_minute']);

        // historique renvoyé
        $this->assertSame(10, $result['historique']['violations']);
        /* MODIF 2026-10-06 : régression corrigée — ces clés étaient en bare
         * 'frontend'/'backend'/'autre'/'inconnu' (aucune colonne de ce nom
         * dans historique, silencieusement ignorées par CollecteController) ;
         * seule 'repartition_frontend' etc. existe réellement. */
        $this->assertSame(2, $result['historique']['repartition_frontend']);
        $this->assertSame(6, $result['historique']['repartition_backend']);
        $this->assertSame(1, $result['historique']['repartition_autre']);
        $this->assertSame(1, $result['historique']['repartition_inconnu']);
        $this->assertArrayNotHasKey('frontend', $result['historique']);
    }

    /**
     * MODIF 2026-10-06 : répartition par scope (MAIN/TEST), globale et par
     * catégorie — facet 'scopes' ajouté aux 3 appels par catégorie déjà
     * existants (aucun appel HTTP supplémentaire).
     */
    public function testScopeFacetsAreAggregatedGloballyAndPerCategory(): void
    {
        $generalPayload = [
            'code' => 200,
            'json' => [
                'paging' => ['total' => 10],
                'total' => 10, 'effortTotal' => 120,
                'facets' => [
                    ['property' => 'severities', 'values' => []],
                    ['property' => 'types', 'values' => []],
                    ['property' => 'directories', 'values' => []],
                ],
            ],
        ];

        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                $generalPayload,
                [
                    'code' => 200,
                    'json' => [
                        'paging' => ['total' => 3], 'total' => 3, 'effortTotal' => 30,
                        'facets' => [[
                            'property' => 'scopes',
                            'values' => [['val' => 'MAIN', 'count' => 2], ['val' => 'TEST', 'count' => 1]],
                        ]],
                    ],
                ],
                [
                    'code' => 200,
                    'json' => [
                        'paging' => ['total' => 2], 'total' => 2, 'effortTotal' => 20,
                        'facets' => [[
                            'property' => 'scopes',
                            'values' => [['val' => 'MAIN', 'count' => 2], ['val' => 'TEST', 'count' => 0]],
                        ]],
                    ],
                ],
                [
                    'code' => 200,
                    'json' => [
                        'paging' => ['total' => 5], 'total' => 5, 'effortTotal' => 70,
                        'facets' => [[
                            'property' => 'scopes',
                            'values' => [['val' => 'MAIN', 'count' => 3], ['val' => 'TEST', 'count' => 2]],
                        ]],
                    ],
                ],
            );

        $this->repo->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);

        $capturedMap = [];
        $this->repo->expects($this->once())
            ->method('insertAnomalie')
            ->with($this->callback(function (array $map) use (&$capturedMap) {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        // Par catégorie : BUG=2/1, VULNERABILITY=2/0, CODE_SMELL=3/2
        $this->assertSame(2, $capturedMap['bug_scope_main']);
        $this->assertSame(1, $capturedMap['bug_scope_test']);
        $this->assertSame(2, $capturedMap['vulnerability_scope_main']);
        $this->assertSame(0, $capturedMap['vulnerability_scope_test']);
        $this->assertSame(3, $capturedMap['code_smell_scope_main']);
        $this->assertSame(2, $capturedMap['code_smell_scope_test']);
        // Globaux = somme des 3 catégories
        $this->assertSame(7, $capturedMap['scope_main']);
        $this->assertSame(3, $capturedMap['scope_test']);
        // Même répartition dans le $historique renvoyé à CollecteController
        $this->assertSame(7, $result['historique']['scope_main']);
        $this->assertSame(3, $result['historique']['scope_test']);
    }

    public function testOrphanIssuesWithoutDirectoryAreReassignedToInconnu(): void
    {
        // paging.total = 12, mais facet directories ne somme qu'à 10 → 2 issues sans directory.
        $generalPayload = [
            'code' => 200,
            'json' => [
                'paging' => ['total' => 12],
                'total' => 12, 'effortTotal' => 120,
                'facets' => [
                    [
                        'property' => 'severities',
                        'values' => [
                            ['val' => 'BLOCKER', 'count' => 2],
                            ['val' => 'CRITICAL', 'count' => 3],
                            ['val' => 'MAJOR', 'count' => 7],
                        ],
                    ],
                    [
                        'property' => 'types',
                        'values' => [
                            ['val' => 'BUG', 'count' => 4],
                            ['val' => 'VULNERABILITY', 'count' => 2],
                            ['val' => 'CODE_SMELL', 'count' => 6],
                        ],
                    ],
                    [
                        'property' => 'directories',
                        'values' => [
                            ['val' => self::MAVEN_KEY . ':presentation/Home.html', 'count' => 2],
                            ['val' => self::MAVEN_KEY . ':controller/Main.java',   'count' => 6],
                            ['val' => self::MAVEN_KEY . ':batch/Run.sh',           'count' => 1],
                            ['val' => self::MAVEN_KEY . ':unknown/X.txt',          'count' => 1],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                $generalPayload,
                ['code' => 200, 'json' => ['paging' => ['total' => 4], 'total' => 4, 'effortTotal' => 30, 'facets' => []]],
                ['code' => 200, 'json' => ['paging' => ['total' => 2], 'total' => 2, 'effortTotal' => 20, 'facets' => []]],
                ['code' => 200, 'json' => ['paging' => ['total' => 6], 'total' => 6, 'effortTotal' => 70, 'facets' => []]],
            );

        $this->repo->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);

        /* MODIF 2026-05-07 : init [] au lieu de null — intelephense ne suit pas l'assignment by-reference dans le callback. */
        $capturedMap = [];
        $this->repo->expects($this->once())
            ->method('insertAnomalie')
            ->with($this->callback(function (array $map) use (&$capturedMap) {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(12, $capturedMap['anomalie_total']);
        // Les 2 issues sans directory doivent atterrir dans inconnu (1 du facet + 2 orphans = 3)
        $this->assertSame(2, $capturedMap['frontend']);
        $this->assertSame(6, $capturedMap['backend']);
        $this->assertSame(1, $capturedMap['autre']);
        $this->assertSame(3, $capturedMap['inconnu']);
        // somme == anomalie_total
        $this->assertSame(
            $capturedMap['anomalie_total'],
            $capturedMap['frontend'] + $capturedMap['backend'] + $capturedMap['autre'] + $capturedMap['inconnu']
        );
    }
}
