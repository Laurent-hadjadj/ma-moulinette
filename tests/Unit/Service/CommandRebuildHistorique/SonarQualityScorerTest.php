<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CommandRebuildHistorique;

use App\Service\CommandRebuildHistorique\SonarQualityScorer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SonarQualityScorerTest extends TestCase
{
    /**
     * Formule de calculate() :
     *   score = 100
     *   - critical penalties : bug_blocker×15, bug_critical×10, vuln_blocker×20, vuln_critical×15
     *   - major penalties    : bug_major×5, vuln_major×8, cs_critical×5, cs_major×2
     *   - quality adjustments :
     *       coverage                <50  → -20
     *                               <70  → -10
     *                               >80  → +5
     *       duplicated_lines_density >20 → -15
     *                               >10  → -5
     *       comment_lines_density   <5 || >40 → -10
     *                               <10 || >35 → -5
     *                               [20;25]   → +2
     *   - clamp [0, 100]
     *   - rating : A≥90 · B≥75 · C≥60 · D≥45 · E≥30 · else F
     */
    #[DataProvider('calculateProvider')]
    public function testCalculate(array $metrics, int $expectedScore, string $expectedRating): void
    {
        $scorer = new SonarQualityScorer($metrics);

        $this->assertSame(
            ['score' => $expectedScore, 'rating' => $expectedRating],
            $scorer->calculate()
        );
    }

    public static function calculateProvider(): array
    {
        // Profil de référence « neutre » : coverage=75, dup=0, comments=15
        // → aucune pénalité ni bonus qualité → on part de 100.
        $neutral = ['coverage' => 75, 'duplicated_lines_density' => 0, 'comment_lines_density' => 15];

        return [
            // ── scénarios penalités critiques ────────────────────────────────
            'bug_blocker -15' => [
                $neutral + ['bug_blocker' => 1],
                85, 'B',
            ],
            'bug_critical -10' => [
                $neutral + ['bug_critical' => 1],
                90, 'A',
            ],
            'vulnerability_blocker -20' => [
                $neutral + ['vulnerability_blocker' => 1],
                80, 'B',
            ],
            'vulnerability_critical -15' => [
                $neutral + ['vulnerability_critical' => 1],
                85, 'B',
            ],

            // ── scénarios penalités majeures ─────────────────────────────────
            'bug_major -5' => [
                $neutral + ['bug_major' => 1],
                95, 'A',
            ],
            'vulnerability_major -8' => [
                $neutral + ['vulnerability_major' => 1],
                92, 'A',
            ],
            'code_smell_critical -5' => [
                $neutral + ['code_smell_critical' => 1],
                95, 'A',
            ],
            'code_smell_major -2' => [
                $neutral + ['code_smell_major' => 1],
                98, 'A',
            ],

            // ── coverage bands ───────────────────────────────────────────────
            'coverage <50 -20' => [
                ['coverage' => 30, 'duplicated_lines_density' => 0, 'comment_lines_density' => 15],
                80, 'B',
            ],
            'coverage 50-70 -10' => [
                ['coverage' => 60, 'duplicated_lines_density' => 0, 'comment_lines_density' => 15],
                90, 'A',
            ],
            'coverage >80 +5' => [
                ['coverage' => 85, 'duplicated_lines_density' => 0, 'comment_lines_density' => 15],
                100, 'A', // 100 + 5 = 105 → clamp à 100
            ],

            // ── duplication bands ────────────────────────────────────────────
            'duplication >20 -15' => [
                ['coverage' => 75, 'duplicated_lines_density' => 25, 'comment_lines_density' => 15],
                85, 'B',
            ],
            'duplication 10-20 -5' => [
                ['coverage' => 75, 'duplicated_lines_density' => 15, 'comment_lines_density' => 15],
                95, 'A',
            ],

            // ── comment bands ────────────────────────────────────────────────
            'comments <5 -10' => [
                ['coverage' => 75, 'duplicated_lines_density' => 0, 'comment_lines_density' => 3],
                90, 'A',
            ],
            'comments >40 -10' => [
                ['coverage' => 75, 'duplicated_lines_density' => 0, 'comment_lines_density' => 45],
                90, 'A',
            ],
            'comments 5-9 -5' => [
                ['coverage' => 75, 'duplicated_lines_density' => 0, 'comment_lines_density' => 8],
                95, 'A',
            ],
            'comments 36-40 -5' => [
                ['coverage' => 75, 'duplicated_lines_density' => 0, 'comment_lines_density' => 37],
                95, 'A',
            ],
            'comments [20;25] +2' => [
                ['coverage' => 75, 'duplicated_lines_density' => 0, 'comment_lines_density' => 22],
                100, 'A', // 100 + 2 → clamp 100
            ],

            // ── clamp lower bound + rating F ─────────────────────────────────
            'extreme penalties → clamp 0 F' => [
                ['bug_blocker' => 10, 'vulnerability_blocker' => 10],
                0, 'F',
            ],

            // ── rating boundaries ────────────────────────────────────────────
            // On utilise vulnerability_blocker (-20/unité) pour calibrer le score final,
            // à partir du profil neutre (score brut = 100).
            'rating boundary A=90' => [
                $neutral + ['vulnerability_major' => 1, 'code_smell_major' => 1],
                90, 'A', // 100 - 8 - 2 = 90
            ],
            'rating boundary B=75' => [
                $neutral + ['bug_blocker' => 1, 'code_smell_major' => 5],
                75, 'B', // 100 - 15 - (2*5) = 75
            ],
            'rating boundary C=60' => [
                $neutral + ['vulnerability_blocker' => 2],
                60, 'C', // 100 - 40 = 60
            ],
            'rating boundary D=45' => [
                $neutral + ['vulnerability_blocker' => 2, 'code_smell_major' => 7, 'bug_major' => 0, 'code_smell_critical' => 0, 'vulnerability_major' => 0],
                46, 'D', // 100 - 40 - 14 = 46 (D ≥45, <60)
            ],
            'rating boundary E=30' => [
                $neutral + ['vulnerability_blocker' => 3, 'code_smell_major' => 5],
                30, 'E', // 100 - 60 - 10 = 30
            ],
            'rating F (<30)' => [
                $neutral + ['vulnerability_blocker' => 4],
                20, 'F', // 100 - 80 = 20
            ],

            // ── défaut 0 pour les clés absentes ──────────────────────────────
            'empty metrics → coverage 0 + comments 0' => [
                [], // tout à 0 : coverage<50 -20, duplication 0, comments<5 -10
                70, 'C', // 100 - 20 - 10 = 70
            ],

            // ── coercition numérique (metric value = string) ─────────────────
            'string numeric metric is coerced' => [
                ['coverage' => '85', 'duplicated_lines_density' => '0', 'comment_lines_density' => '15'],
                100, 'A', // identique au cas coverage >80
            ],
        ];
    }

    #[DataProvider('calculateStrictProvider')]
    public function testCalculateStrict(array $metrics, array $expected): void
    {
        $scorer = new SonarQualityScorer($metrics);

        $this->assertSame($expected, $scorer->calculateStrict());
    }

    public static function calculateStrictProvider(): array
    {
        $neutral = ['coverage' => 75, 'duplicated_lines_density' => 0, 'comment_lines_density' => 15];

        return [
            // vulnerability_blocker prioritaire sur bug_blocker et sur calculate()
            'vulnerability_blocker → 0/F' => [
                $neutral + ['vulnerability_blocker' => 1, 'bug_blocker' => 5],
                ['score' => 0, 'rating' => 'F'],
            ],
            // bug_blocker sans vuln_blocker → 20/E (ne tient pas compte du reste)
            'bug_blocker → 20/E' => [
                $neutral + ['bug_blocker' => 1, 'code_smell_critical' => 100],
                ['score' => 20, 'rating' => 'E'],
            ],
            // Sans blocker → délègue à calculate()
            'no blocker → delegates to calculate' => [
                $neutral + ['bug_critical' => 1], // -10
                ['score' => 90, 'rating' => 'A'],
            ],
        ];
    }
}
