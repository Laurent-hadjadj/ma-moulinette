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

use App\Service\CommandRebuildHistorique\SonarQualityScorer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SonarQualityScorerTest extends TestCase
{
    /**
     * Métriques neutres : coverage/duplication/comment_lines_density placées
     * dans les tranches "sans effet" de applyQualityAdjustments(), tous les
     * compteurs de bugs/vulnérabilités/code smells à 0. Score de départ = 100.
     *
     * @param array<string, int|float|string> $overrides
     *
     * @return array<string, int|float|string>
     */
    private function neutralMetrics(array $overrides = []): array
    {
        return array_merge([
            'coverage' => 75,
            'duplicated_lines_density' => 5,
            'comment_lines_density' => 15,
            'bug_blocker' => 0,
            'bug_critical' => 0,
            'bug_major' => 0,
            'vulnerability_blocker' => 0,
            'vulnerability_critical' => 0,
            'vulnerability_major' => 0,
            'code_smell_critical' => 0,
            'code_smell_major' => 0,
        ], $overrides);
    }

    public function testCalculateWithNeutralMetricsReturnsPerfectScore(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics());

        $this->assertSame(['score' => 100, 'rating' => 'A'], $scorer->calculate());
    }

    public function testCalculateWithNoMetricsAtAllAppliesLowCoverageAndLowCommentPenalties(): void
    {
        // Aucune métrique fournie : get() retombe sur 0 pour chaque clé.
        // coverage=0 (<50 -> -20), duplication=0 (neutre), comments=0 (<5 -> -10).
        $scorer = new SonarQualityScorer([]);

        $this->assertSame(['score' => 70, 'rating' => 'C'], $scorer->calculate());
    }

    /**
     * @return iterable<string, array{0: int, 1: int, 2: string}>
     */
    public static function bugBlockerRatingProvider(): iterable
    {
        yield 'aucun bug blocker -> A' => [0, 100, 'A'];
        yield '1 bug blocker -> B' => [1, 85, 'B'];
        yield '2 bugs blocker -> C' => [2, 70, 'C'];
        yield '3 bugs blocker -> D' => [3, 55, 'D'];
        yield '4 bugs blocker -> E' => [4, 40, 'E'];
        yield '5 bugs blocker -> F' => [5, 25, 'F'];
    }

    #[DataProvider('bugBlockerRatingProvider')]
    public function testGetRatingBoundariesViaBugBlockerPenalty(int $bugBlocker, int $expectedScore, string $expectedRating): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['bug_blocker' => $bugBlocker]));

        $this->assertSame(['score' => $expectedScore, 'rating' => $expectedRating], $scorer->calculate());
    }

    public function testCriticalPenaltiesAreAppliedForEachIssueType(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics([
            'bug_blocker' => 1,             // -15
            'bug_critical' => 1,             // -10
            'vulnerability_blocker' => 1,    // -20
            'vulnerability_critical' => 1,   // -15
        ]));

        // 100 - 15 - 10 - 20 - 15 = 40
        $this->assertSame(['score' => 40, 'rating' => 'E'], $scorer->calculate());
    }

    public function testMajorPenaltiesAreAppliedForEachIssueType(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics([
            'bug_major' => 1,               // -5
            'vulnerability_major' => 1,      // -8
            'code_smell_critical' => 1,      // -5
            'code_smell_major' => 1,         // -2
        ]));

        // 100 - 5 - 8 - 5 - 2 = 80
        $this->assertSame(['score' => 80, 'rating' => 'B'], $scorer->calculate());
    }

    public function testHighCoverageGrantsBonusClampedAtHundred(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['coverage' => 95]));

        // +5 bonus mais clamp(105, 0, 100) = 100
        $this->assertSame(['score' => 100, 'rating' => 'A'], $scorer->calculate());
    }

    public function testMediumCoverageAppliesTenPointPenalty(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['coverage' => 60]));

        $this->assertSame(['score' => 90, 'rating' => 'A'], $scorer->calculate());
    }

    public function testHighDuplicationAppliesFifteenPointPenalty(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['duplicated_lines_density' => 25]));

        $this->assertSame(['score' => 85, 'rating' => 'B'], $scorer->calculate());
    }

    public function testModerateDuplicationAppliesFivePointPenalty(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['duplicated_lines_density' => 15]));

        $this->assertSame(['score' => 95, 'rating' => 'A'], $scorer->calculate());
    }

    public function testIdealCommentDensityGrantsBonus(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['comment_lines_density' => 22]));

        $this->assertSame(['score' => 100, 'rating' => 'A'], $scorer->calculate());
    }

    public function testExcessiveCommentDensityAppliesTenPointPenalty(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['comment_lines_density' => 45]));

        $this->assertSame(['score' => 90, 'rating' => 'A'], $scorer->calculate());
    }

    public function testScoreIsClampedToZeroWhenPenaltiesExceedHundred(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['bug_blocker' => 20]));

        $this->assertSame(['score' => 0, 'rating' => 'F'], $scorer->calculate());
    }

    public function testCalculateStrictReturnsZeroWhenAnyVulnerabilityBlocker(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['vulnerability_blocker' => 1]));

        $this->assertSame(['score' => 0, 'rating' => 'F'], $scorer->calculateStrict());
    }

    public function testCalculateStrictReturnsTwentyWhenAnyBugBlockerWithoutVulnerabilityBlocker(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics(['bug_blocker' => 1]));

        $this->assertSame(['score' => 20, 'rating' => 'E'], $scorer->calculateStrict());
    }

    public function testCalculateStrictDelegatesToCalculateWhenNoBlockerIssues(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics());

        $this->assertSame(['score' => 100, 'rating' => 'A'], $scorer->calculateStrict());
    }

    public function testCalculateStrictPrioritisesVulnerabilityBlockerOverBugBlocker(): void
    {
        $scorer = new SonarQualityScorer($this->neutralMetrics([
            'bug_blocker' => 1,
            'vulnerability_blocker' => 1,
        ]));

        $this->assertSame(['score' => 0, 'rating' => 'F'], $scorer->calculateStrict());
    }
}
