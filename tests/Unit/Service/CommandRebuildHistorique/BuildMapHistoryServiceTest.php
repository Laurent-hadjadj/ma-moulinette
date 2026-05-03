<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CommandRebuildHistorique;

use App\Service\CommandRebuildHistorique\BuildMapHistoryService;
use App\Service\ExtractName;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BuildMapHistoryServiceTest extends TestCase
{
    /** @var ExtractName&MockObject */
    private MockObject $extractName;

    private BuildMapHistoryService $service;

    protected function setUp(): void
    {
        $this->extractName = $this->createMock(ExtractName::class);
        $this->extractName->method('extractNameFromMavenKey')->willReturn('my-project');

        $this->service = new BuildMapHistoryService($this->extractName);
    }

    // ─────────────────────── metricsKey(int $version) ───────────────────────

    public function testMetricsKeyForCoreVersionsReturnsCoreMetricsOnly(): void
    {
        $query = $this->service->metricsKey(8);

        // Doit contenir les métriques _CORE
        $this->assertStringContainsString('alert_status', $query);
        $this->assertStringContainsString('bugs', $query);
        $this->assertStringContainsString('coverage', $query);
        $this->assertStringContainsString('code_smells', $query);

        // Ne doit PAS contenir les spécificités LTA 10 ou 2024
        $this->assertStringNotContainsString('accepted_issues', $query);
        $this->assertStringNotContainsString('software_quality_blocker_issues', $query);
    }

    public function testMetricsKeyForLta10ReturnsCoreAndLta10Metrics(): void
    {
        $query = $this->service->metricsKey(10);

        $this->assertStringContainsString('bugs', $query);            // core
        $this->assertStringContainsString('accepted_issues', $query); // LTA 10
        $this->assertStringContainsString('reliability_issues', $query);
        $this->assertStringNotContainsString('software_quality_blocker_issues', $query); // 2024 uniquement
    }

    public function testMetricsKeyForLta2024ReturnsCorePlus10Plus2024Metrics(): void
    {
        $query = $this->service->metricsKey(2024);

        $this->assertStringContainsString('bugs', $query);
        $this->assertStringContainsString('accepted_issues', $query);
        $this->assertStringContainsString('software_quality_blocker_issues', $query);
        $this->assertStringContainsString('software_quality_maintainability_issues', $query);
    }

    // ─────────────────────── metricsRebuild happy paths ───────────────────────

    public function testMetricsRebuildDefaultCollectReturnsOnlyCoreMetricsAndSkipsCustomBlock(): void
    {
        $result = $this->service->metricsRebuild(
            [],
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
            // $collect = 'measures' par défaut
        );

        // Le mode 'measures' N'INCLUT PAS le bloc custom : pas de maven_key, pas de version_release.
        $this->assertArrayNotHasKey('maven_key', $result);
        $this->assertArrayNotHasKey('version_release', $result);
        $this->assertArrayNotHasKey('mode_collecte', $result);

        // Mais il INCLUT l'ensemble des clés core
        $this->assertArrayHasKey('alert_status', $result);
        $this->assertArrayHasKey('coverage', $result);
        $this->assertArrayHasKey('bugs', $result);
        $this->assertArrayHasKey('sqale_rating', $result);
    }

    public function testMetricsRebuildAnalyticsCollectIncludesCustomBlockAndProjectInfo(): void
    {
        // setUp a déjà stubé extractNameFromMavenKey → 'my-project' ; on s'appuie dessus

        $result = $this->service->metricsRebuild(
            [],
            [
                'analysisKey' => 'AY1',
                'version' => '1.2.3',
                'date' => '2026-04-22T09:30:00+0000',
                'version_release' => 5,
                'version_snapshot' => 2,
                'version_autre' => 1,
            ],
            'com.acme:app',
            'analytics'
        );

        // Informations projet propagées
        $this->assertSame('com.acme:app', $result['maven_key']);
        $this->assertSame('AY1', $result['analyse_key']);
        $this->assertSame('1.2.3', $result['version']);
        $this->assertSame('my-project', $result['project_name']);
        $this->assertInstanceOf(\DateTime::class, $result['date_version']);

        // Informations collecte injectées
        $this->assertSame('REBUILD', $result['mode_collecte']);
        $this->assertSame('cli', $result['utilisateur_collecte']);

        // Compteurs de version depuis $analysis
        $this->assertSame(5, $result['version_release']);
        $this->assertSame(2, $result['version_snapshot']);
        $this->assertSame(1, $result['version_autre']);

        // Les clés dynamiques bug/vulnerability/code_smell × blocker/critical/.../info sont initialisées à null
        $this->assertArrayHasKey('bug_blocker', $result);
        $this->assertArrayHasKey('bug_critical', $result);
        $this->assertArrayHasKey('vulnerability_blocker', $result);
        $this->assertArrayHasKey('code_smell_info', $result);
        $this->assertNull($result['bug_blocker']);
    }

    public function testMetricsRebuildCastsAndPassesThroughProvidedMeasures(): void
    {
        $result = $this->service->metricsRebuild(
            [
                'alert_status' => 'OK',
                'ncloc' => '12345',
                'files' => '42',
                'coverage' => '87.4',
                'comment_lines_density' => '22.5',
                'bugs' => '3',
                'sqale_rating' => '2',
                'reliability_rating' => '1',
                'security_rating' => '3',
                'duplicated_lines_density' => '4.5',
                'complexity' => '500',
                'cognitive_complexity' => '100',
                'tests' => '250',
            ],
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertSame('OK', $result['alert_status']);
        $this->assertSame(12345, $result['ncloc']);
        $this->assertSame(42, $result['files']);
        $this->assertSame(87.4, $result['coverage']);
        $this->assertSame(22.5, $result['comment_lines_density']);
        $this->assertSame(3, $result['bugs']);
        $this->assertSame(250, $result['tests']);

        // Ratings lettre (via ratingToLetter)
        $this->assertSame('B', $result['sqale_rating']);
        $this->assertSame('A', $result['reliability_rating']);
        $this->assertSame('C', $result['security_rating']);

        // Ratios complexité = ncloc / complexity
        $this->assertSame(24.7, $result['complexity_ratio']); // 12345 / 500 ≈ 24.69 → round 1 = 24.7
        $this->assertSame(123.5, $result['cognitive_complexity_ratio']);
    }

    public function testMetricsRebuildDefaultsAlertStatusToUnknownWhenMissing(): void
    {
        $result = $this->service->metricsRebuild(
            [],
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertSame('UNKNOWN', $result['alert_status']);
    }

    public function testMetricsRebuildHandlesZeroComplexityWithoutDivisionByZero(): void
    {
        $result = $this->service->metricsRebuild(
            ['ncloc' => '1000', 'complexity' => '0', 'cognitive_complexity' => '0'],
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertNull($result['complexity_ratio']);
        $this->assertNull($result['cognitive_complexity_ratio']);
    }

    // ─────────────────────── rating helpers (via metricsRebuild) ───────────────────────

    #[DataProvider('coverageRatingProvider')]
    public function testCoverageRatingCoversEachBand(?float $coverage, ?string $expected): void
    {
        $measures = $coverage !== null ? ['coverage' => (string) $coverage] : [];

        $result = $this->service->metricsRebuild(
            $measures,
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertSame($expected, $result['coverage_rating']);
    }

    public static function coverageRatingProvider(): array
    {
        return [
            'null → null'   => [null, null],
            '85 → A'        => [85.0, 'A'],
            '75 → B'        => [75.0, 'B'],
            '60 → C'        => [60.0, 'C'],
            '35 → D'        => [35.0, 'D'], // bande corrigée [30;50)
            '15 → E'        => [15.0, 'E'],
        ];
    }

    #[DataProvider('duplicationRatingProvider')]
    public function testDuplicationRatingCoversEachBand(?float $density, ?string $expected): void
    {
        $measures = $density !== null ? ['duplicated_lines_density' => (string) $density] : [];

        $result = $this->service->metricsRebuild(
            $measures,
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertSame($expected, $result['duplicated_lines_rating']);
    }

    public static function duplicationRatingProvider(): array
    {
        return [
            'null → null' => [null, null],
            '2 → A'       => [2.0, 'A'],
            '4 → B'       => [4.0, 'B'],
            '8 → C'       => [8.0, 'C'],
            '15 → D'      => [15.0, 'D'],
            '30 → E'      => [30.0, 'E'],
        ];
    }

    #[DataProvider('commentDensityRatingProvider')]
    public function testCommentDensityRatingCoversEachBand(float $ratio, string $expected): void
    {
        $result = $this->service->metricsRebuild(
            ['comment_lines_density' => (string) $ratio],
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertSame($expected, $result['comment_lines_rating']);
    }

    public static function commentDensityRatingProvider(): array
    {
        return [
            '3 → E (trop peu)'         => [3.0, 'E'],
            '10 → D (zone basse)'      => [10.0, 'D'], // fix 2026-05-03 : avant le gap retournait '--'
            '20 → A'                   => [20.0, 'A'],
            '27 → B'                   => [27.0, 'B'],
            '32 → C'                   => [32.0, 'C'],
            '37 → D'                   => [37.0, 'D'],
            '45 → E (trop)'            => [45.0, 'E'],
        ];
    }

    #[DataProvider('complexityRatingProvider')]
    public function testComplexityRatingCoversEachBand(int $ncloc, int $complexity, string $expected): void
    {
        $result = $this->service->metricsRebuild(
            ['ncloc' => (string) $ncloc, 'complexity' => (string) $complexity],
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertSame($expected, $result['complexity_rating']);
    }

    public static function complexityRatingProvider(): array
    {
        // ratio = ncloc / complexity, rating sur le ratio
        return [
            'ratio 2 → A'  => [200, 100, 'A'], // 2.0
            'ratio 5 → B'  => [500, 100, 'B'], // 5.0
            'ratio 10 → C' => [1000, 100, 'C'],
            'ratio 15 → D' => [1500, 100, 'D'],
            'ratio 20 → E' => [2000, 100, 'E'],
        ];
    }

    #[DataProvider('ratingToLetterProvider')]
    public function testRatingToLetterViaReliabilityRating(string $value, ?string $expected): void
    {
        $result = $this->service->metricsRebuild(
            ['reliability_rating' => $value],
            ['analysisKey' => 'AY1', 'version' => '1.0', 'date' => '2026-04-22T00:00:00+0000'],
            'com.acme:app'
        );

        $this->assertSame($expected, $result['reliability_rating']);
    }

    public static function ratingToLetterProvider(): array
    {
        return [
            '1 → A' => ['1', 'A'],
            '2 → B' => ['2', 'B'],
            '3 → C' => ['3', 'C'],
            '4 → D' => ['4', 'D'],
            '5 → E' => ['5', 'E'],
            '9 → --' => ['9', '--'],
        ];
    }
}
