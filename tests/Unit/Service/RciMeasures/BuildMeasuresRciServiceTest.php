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

namespace App\Tests\Unit\Service\RciMeasures;

use App\Service\RciMeasures\BuildMeasuresRciService;
use App\Service\{ClientService, UrlBuilderService};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BuildMeasuresRciServiceTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL = 'https://sonar.example.com/api/...';

    /** @var ClientService&MockObject */
    private MockObject $client;
    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;
    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;

    private BuildMeasuresRciService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);
        $this->params->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['module.frontend', 'presentation,webapp,front,frontend'],
            ['module.backend', 'back,backend,api,service,dao'],
            ['module.autre', 'batch,etl,pipeline'],
        ]);

        $this->service = new BuildMeasuresRciService(
            $this->client,
            $this->urlBuilder,
            $this->params,
            $this->logger
        );
    }

    // ─────────────────────── calculateRci(?int $ncloc, int $poidsTotal) ───────

    public function testCalculateRciReturnsNullWhenNclocIsNull(): void
    {
        $this->assertNull($this->service->calculateRci(null, 100));
    }

    public function testCalculateRciReturnsNullWhenNclocIsZeroOrNegative(): void
    {
        $this->assertNull($this->service->calculateRci(0, 0));
        $this->assertNull($this->service->calculateRci(-5, 0));
    }

    #[DataProvider('calculateRciProvider')]
    public function testCalculateRciFormula(int $ncloc, int $poidsTotal, float $expected): void
    {
        $this->assertSame($expected, $this->service->calculateRci($ncloc, $poidsTotal));
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: float}>
     */
    public static function calculateRciProvider(): array
    {
        return [
            'aucune anomalie -> 100' => [1000, 0, 100.0],
            'poids = ncloc -> 0' => [1000, 1000, 0.0],
            'poids > ncloc -> borné à 0 (pas négatif)' => [100, 500, 0.0],
            'cas réel validé contre le plugin (webapp)' => [16590, 3725, 77.55],
        ];
    }

    // ─────────────────────── getRCIRating(?float $rci) ─────────────────────

    public function testGetRciRatingReturnsNullWhenRciIsNull(): void
    {
        $this->assertNull($this->service->getRCIRating(null));
    }

    #[DataProvider('rciRatingProvider')]
    public function testGetRciRatingAppliesSqaleBandsOnDebtRatio(float $rci, string $expected): void
    {
        $this->assertSame($expected, $this->service->getRCIRating($rci));
    }

    /**
     * @return array<string, array{0: float, 1: string}>
     */
    public static function rciRatingProvider(): array
    {
        return [
            'RCI global 40,6 → E (taux de dette 59,4%)' => [40.6, 'E'],
            'RCI module 77,5 → D (taux de dette 22,5%)' => [77.5, 'D'],
            'RCI 100 (aucune dette) → A' => [100.0, 'A'],
            'RCI 95 (borne A) → A' => [95.0, 'A'],
            'RCI 90 (borne B) → B' => [90.0, 'B'],
            'RCI 80 (borne C) → C' => [80.0, 'C'],
            'RCI 50 (borne D) → D' => [50.0, 'D'],
            'RCI 0 (très critique) → E' => [0.0, 'E'],
        ];
    }

    // ─────────────────────── weightedWeights(...) ──────────────────────────

    public function testWeightedWeightsAppliesSeverityWeights(): void
    {
        // BLOCKER=10, CRITICAL=5, MAJOR=3, MINOR=1, INFO=0
        $result = $this->service->weightedWeights(2, 1, 3, 4, 100);

        $this->assertSame(2 * 10 + 1 * 5 + 3 * 3 + 4 * 1, $result);
    }

    public function testWeightedWeightsIgnoresInfoCount(): void
    {
        $this->assertSame(0, $this->service->weightedWeights(0, 0, 0, 0, 999));
    }

    // ─────────────────────── calculateGlobalMeasures(...) ───────────────────

    public function testCalculateGlobalMeasuresParsesMeasuresIntoMap(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'component' => [
                    'measures' => [
                        ['metric' => 'ncloc', 'value' => '8125'],
                        ['metric' => 'blocker_violations', 'value' => '2'],
                        ['metric' => 'critical_violations', 'value' => '1'],
                    ],
                ],
            ],
        ]);

        $result = $this->service->calculateGlobalMeasures(self::MAVEN_KEY, 9);

        $this->assertSame(200, $result['code']);
        $this->assertSame(8125, $result['global_measures']['ncloc']);
        $this->assertSame(2, $result['global_measures']['blocker_violations']);
        $this->assertSame(2, $result['severities']['BLOCKER']);
        $this->assertSame(1, $result['severities']['CRITICAL']);
        $this->assertSame(0, $result['severities']['MAJOR']);
    }

    public function testCalculateGlobalMeasuresNormalizesNewTaxonomyFrom2024(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'component' => [
                    'measures' => [
                        ['metric' => 'ncloc', 'value' => '8125'],
                        ['metric' => 'software_quality_blocker_issues', 'value' => '4'],
                        ['metric' => 'software_quality_high_issues', 'value' => '2'],
                    ],
                ],
            ],
        ]);

        $result = $this->service->calculateGlobalMeasures(self::MAVEN_KEY, 2025);

        $this->assertSame(4, $result['severities']['BLOCKER']);
        $this->assertSame(2, $result['severities']['CRITICAL']);
    }

    public function testCalculateGlobalMeasuresReturnsErrorArrayOnSonarFailureInsteadOfThrowing(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 500,
            'erreur' => 'Erreur serveur SonarQube',
        ]);

        $result = $this->service->calculateGlobalMeasures(self::MAVEN_KEY, 9);

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayNotHasKey('global_measures', $result);
    }

    // ─────────────────────── calculateMeasuresByModule(...) ─────────────────

    public function testCalculateMeasuresByModuleClassifiesAndSumsPerModule(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'components' => [
                    [
                        'path' => 'frontend/src/app.component.ts',
                        'measures' => [
                            ['metric' => 'ncloc', 'value' => '100'],
                            ['metric' => 'blocker_violations', 'value' => '2'],
                        ],
                    ],
                    [
                        'path' => 'backend/api/UserService.java',
                        'measures' => [
                            ['metric' => 'ncloc', 'value' => '50'],
                            ['metric' => 'critical_violations', 'value' => '1'],
                        ],
                    ],
                    [
                        'path' => 'README.md',
                        'measures' => [
                            ['metric' => 'ncloc', 'value' => '5'],
                        ],
                    ],
                ],
                'paging' => ['total' => 3],
            ],
        ]);

        $result = $this->service->calculateMeasuresByModule(self::MAVEN_KEY, 9);

        $this->assertSame(200, $result['code']);
        $this->assertSame(100, $result['ncloc']['frontend']);
        $this->assertSame(50, $result['ncloc']['backend']);
        $this->assertSame(5, $result['ncloc']['inconnu']);
        $this->assertSame(0, $result['ncloc']['autre']);
        $this->assertSame(2, $result['severities']['frontend']['BLOCKER']);
        $this->assertSame(1, $result['severities']['backend']['CRITICAL']);
    }

    public function testCalculateMeasuresByModuleUsesNewTaxonomyFrom2024(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'components' => [
                    [
                        'path' => 'frontend/src/app.component.ts',
                        'measures' => [
                            ['metric' => 'ncloc', 'value' => '100'],
                            ['metric' => 'software_quality_blocker_issues', 'value' => '3'],
                        ],
                    ],
                ],
                'paging' => ['total' => 1],
            ],
        ]);

        $result = $this->service->calculateMeasuresByModule(self::MAVEN_KEY, 2025);

        $this->assertSame(3, $result['severities']['frontend']['BLOCKER']);
    }

    public function testCalculateMeasuresByModuleReturnsErrorArrayOnSonarFailure(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 404,
            'erreur' => 'Projet introuvable',
        ]);

        $result = $this->service->calculateMeasuresByModule(self::MAVEN_KEY, 9);

        $this->assertSame(404, $result['code']);
        $this->assertArrayHasKey('erreur', $result);
    }
}
