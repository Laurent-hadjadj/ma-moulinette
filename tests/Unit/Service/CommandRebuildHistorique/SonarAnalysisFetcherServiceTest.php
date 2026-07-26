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

namespace App\Tests\Unit\Service\CommandRebuildHistorique;

use App\Service\ClientService;
use App\Service\CommandRebuildHistorique\SonarAnalysisFetcherService;
use App\Service\UrlBuilderService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class SonarAnalysisFetcherServiceTest extends TestCase
{
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL = 'https://sonar.example.com/api/project_analyses/search?...';

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private SonarAnalysisFetcherService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->params->method('get')->willReturn(self::SONAR_URL);
        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);

        $this->service = new SonarAnalysisFetcherService(
            $this->client,
            $this->urlBuilder,
            $this->params,
            $this->logger
        );
    }

    public function testFetchAnalysesReturnsSinglePageSortedByDateDescending(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->with(self::BUILT_URL)
            ->willReturn([
                'code' => 200,
                'json' => [
                    'analyses' => [
                        ['key' => 'A1', 'date' => '2026-01-01T10:00:00+0000', 'projectVersion' => '1.0.0'],
                        ['key' => 'A2', 'date' => '2026-03-15T10:00:00+0000', 'projectVersion' => '1.2.0'],
                        ['key' => 'A3', 'date' => '2026-02-10T10:00:00+0000', 'projectVersion' => '1.1.0'],
                    ],
                    'paging' => ['total' => 3],
                ],
            ]);

        $result = $this->service->fetchAnalyses('fr.ma-moulinette:ma-moulinette');

        // Tri décroissant par date
        $this->assertSame(['A2', 'A3', 'A1'], array_column($result, 'analysisKey'));
        $this->assertSame(['1.2.0', '1.1.0', '1.0.0'], array_column($result, 'version'));
    }

    public function testFetchAnalysesPaginatesUntilTotalReached(): void
    {
        // 2 pages (total=501 > pageSize=500) → deux appels HTTP
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                [
                    'code' => 200,
                    'json' => [
                        'analyses' => [
                            ['key' => 'A1', 'date' => '2026-01-01T00:00:00+0000', 'projectVersion' => '1.0'],
                        ],
                        'paging' => ['total' => 501],
                    ],
                ],
                [
                    'code' => 200,
                    'json' => [
                        'analyses' => [
                            ['key' => 'A2', 'date' => '2026-02-01T00:00:00+0000', 'projectVersion' => '1.1'],
                        ],
                        'paging' => ['total' => 501],
                    ],
                ],
            );

        $result = $this->service->fetchAnalyses('fr.ma-moulinette:ma-moulinette');

        $this->assertCount(2, $result);
    }

    public function testFetchAnalysesStopsAndLogsErrorWhenApiReturnsNot200(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'json' => []]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[SonarAnalysisFetcher] ❌ Erreur API Sonar',
                $this->callback(fn (array $ctx) => $ctx['status'] === 503)
            );

        // Pas d'exception : la méthode sort de la boucle et retourne un tableau (vide ici)
        $this->assertSame([], $this->service->fetchAnalyses('fr.ma-moulinette:ma-moulinette'));
    }

    /**
     * @param array{key: string, date: string, projectVersion?: string, events?: array<int, array{category: string, name: string}>} $analysis
     */
    #[DataProvider('versionExtractionProvider')]
    public function testFetchAnalysesExtractsVersionFromVariousSources(array $analysis, string $expectedVersion): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => [
                    'analyses' => [$analysis],
                    'paging' => ['total' => 1],
                ],
            ]);

        $result = $this->service->fetchAnalyses('fr.ma-moulinette:ma-moulinette');

        $this->assertSame($expectedVersion, $result[0]['version']);
    }

    /**
     * @return array<string, array{0: array{key: string, date: string, projectVersion?: string, events?: array<int, array{category: string, name: string}>}, 1: string}>
     */
    public static function versionExtractionProvider(): array
    {
        return [
            'from projectVersion' => [
                ['key' => 'K', 'date' => '2026-01-01T00:00:00+0000', 'projectVersion' => '2.3.4'],
                '2.3.4',
            ],
            'from events category=VERSION when projectVersion empty' => [
                [
                    'key' => 'K',
                    'date' => '2026-01-01T00:00:00+0000',
                    'projectVersion' => '',
                    'events' => [
                        ['category' => 'OTHER', 'name' => 'noise'],
                        ['category' => 'VERSION', 'name' => '9.8.7'],
                    ],
                ],
                '9.8.7',
            ],
            'fallback to unknown when nothing matches' => [
                [
                    'key' => 'K',
                    'date' => '2026-01-01T00:00:00+0000',
                    'events' => [['category' => 'OTHER', 'name' => 'x']],
                ],
                'unknown',
            ],
            'fallback to unknown when no events at all' => [
                ['key' => 'K', 'date' => '2026-01-01T00:00:00+0000'],
                'unknown',
            ],
        ];
    }

    public function testFetchLatestAnalysesPerVersionKeepsNewestAnalysisByVersion(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => [
                    'analyses' => [
                        ['key' => 'A_old_v1', 'date' => '2026-01-01T00:00:00+0000', 'projectVersion' => '1.0'],
                        ['key' => 'A_new_v1', 'date' => '2026-03-01T00:00:00+0000', 'projectVersion' => '1.0'],
                        ['key' => 'A_v2',     'date' => '2026-02-01T00:00:00+0000', 'projectVersion' => '2.0'],
                    ],
                    'paging' => ['total' => 3],
                ],
            ]);

        $result = $this->service->fetchLatestAnalysesPerVersion('fr.ma-moulinette:ma-moulinette');

        // On garde 1 analyse par version — les plus récentes
        $keys = array_column($result, 'analysisKey');
        $this->assertCount(2, $result);
        $this->assertContains('A_new_v1', $keys);
        $this->assertContains('A_v2', $keys);
        $this->assertNotContains('A_old_v1', $keys);
    }

    /**
     * @param array<int, array{version: string, date: string}>          $analyses
     * @param array<int, array{release: int, snapshot: int, autre: int}> $expected
     */
    #[DataProvider('versionTypeProvider')]
    public function testComputeVersionCountersTagsAnalysesByDetectedType(array $analyses, array $expected): void
    {
        $result = $this->service->computeVersionCounters($analyses);

        // Tri croissant par date dans la sortie, donc on peut mapper par index
        foreach ($expected as $i => $expectedCounters) {
            $this->assertSame(
                $expectedCounters,
                [
                    'release' => $result[$i]['version_release'],
                    'snapshot' => $result[$i]['version_snapshot'],
                    'autre' => $result[$i]['version_autre'],
                ],
                sprintf('Analyse index %d', $i)
            );
        }
    }

    /**
     * @return array<string, array{0: array<int, array{version: string, date: string}>, 1: array<int, array{release: int, snapshot: int, autre: int}>}>
     */
    public static function versionTypeProvider(): array
    {
        // Entrée : 1 release, 1 snapshot, 1 rc (autre), 1 sans suffixe (release)
        $analyses = [
            ['version' => '1.0.0',          'date' => '2026-01-01T00:00:00+0000'],
            ['version' => '1.1.0-SNAPSHOT', 'date' => '2026-01-02T00:00:00+0000'],
            ['version' => '1.2.0-RC1',      'date' => '2026-01-03T00:00:00+0000'],
            ['version' => '1.3.0-release',  'date' => '2026-01-04T00:00:00+0000'],
        ];

        return [
            'cumulative counters progress in chronological order' => [
                $analyses,
                [
                    // 1.0.0 = release
                    ['release' => 1, 'snapshot' => 0, 'autre' => 0],
                    // 1.1.0-SNAPSHOT = snapshot
                    ['release' => 1, 'snapshot' => 1, 'autre' => 0],
                    // 1.2.0-RC1 = autre
                    ['release' => 1, 'snapshot' => 1, 'autre' => 1],
                    // 1.3.0-release = release
                    ['release' => 2, 'snapshot' => 1, 'autre' => 1],
                ],
            ],
        ];
    }

    public function testComputeVersionCountersDetectsAlphaBetaAndMilestoneAsAutre(): void
    {
        $result = $this->service->computeVersionCounters([
            ['version' => '1.0.0-alpha', 'date' => '2026-01-01T00:00:00+0000'],
            ['version' => '1.0.0-beta',  'date' => '2026-01-02T00:00:00+0000'],
            ['version' => '1.0.0-M3',    'date' => '2026-01-03T00:00:00+0000'],
        ]);

        $this->assertSame(3, $result[2]['version_autre']);
        $this->assertSame(0, $result[2]['version_release']);
        $this->assertSame(0, $result[2]['version_snapshot']);
    }
}
