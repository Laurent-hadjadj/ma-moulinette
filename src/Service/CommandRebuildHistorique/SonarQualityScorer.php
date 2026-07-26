<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service\CommandRebuildHistorique;

/**
 * [Description SonarQualityScorer]
 */
class SonarQualityScorer
{
    /** @var array<string, int|float|string> */
    private array $metrics;
    private int $score = 100;

    /**
     * @param array<string, int|float|string> $metrics
     */
    public function __construct(array $metrics)
    {
        $this->metrics = $metrics;
    }

    /**
     * [Description for calculate]
     *
     * @return array{score: int, rating: string}
     *
     * Created at: 23/03/2026 02:52:06 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function calculate(): array
    {
        $this->applyCriticalPenalties();
        $this->applyMajorPenalties();
        $this->applyQualityAdjustments();

        $this->score = $this->clamp($this->score, 0, 100);

        return [
            'score' => $this->score,
            'rating' => $this->getRating($this->score),
        ];
    }

    /**
     * [Description for applyCriticalPenalties]
     * 🔴 CRITIQUE
     *
     * @return void
     *
     * Created at: 23/03/2026 02:52:14 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function applyCriticalPenalties(): void
    {
        $this->score -= $this->get('bug_blocker') * 15;
        $this->score -= $this->get('bug_critical') * 10;

        $this->score -= $this->get('vulnerability_blocker') * 20;
        $this->score -= $this->get('vulnerability_critical') * 15;
    }

    /**
     * [Description for applyMajorPenalties]
     * 🟠 IMPORTANT
     *
     * @return void
     *
     * Created at: 23/03/2026 02:52:18 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function applyMajorPenalties(): void
    {
        $this->score -= $this->get('bug_major') * 5;
        $this->score -= $this->get('vulnerability_major') * 8;

        $this->score -= $this->get('code_smell_critical') * 5;
        $this->score -= $this->get('code_smell_major') * 2;
    }

    /**
     * [Description for applyQualityAdjustments]
     * 🟡 QUALITÉ GLOBALE
     *
     * @return void
     *
     * Created at: 23/03/2026 02:52:22 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function applyQualityAdjustments(): void
    {
        $coverage = $this->get('coverage');
        $duplication = $this->get('duplicated_lines_density');
        $comments = $this->get('comment_lines_density');

        // Coverage
        if ($coverage < 50) {
            $this->score -= 20;
        } elseif ($coverage < 70) {
            $this->score -= 10;
        } elseif ($coverage > 80) {
            $this->score += 5;
        }

        // Duplication
        if ($duplication > 20) {
            $this->score -= 15;
        } elseif ($duplication > 10) {
            $this->score -= 5;
        }

        // Comment density
        if ($comments < 5 || $comments > 40) {
            $this->score -= 10;
        } elseif ($comments < 10 || $comments > 35) {
            $this->score -= 5;
        } elseif ($comments >= 20 && $comments <= 25) {
            $this->score += 2;
        }
    }

    /**
     * [Description for getRating]
     * 🏆 RATING
     *
     * @param int $score
     *
     * @return string
     *
     * Created at: 23/03/2026 02:52:30 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getRating(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 45 => 'D',
            $score >= 30 => 'E',
            default => 'F',
        };
    }

    /**
     * [Description for get]
     * 🛠️ UTILS
     *
     * @param string $key
     *
     * @return float
     *
     * Created at: 23/03/2026 02:52:33 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function get(string $key): float
    {
        return (float) ($this->metrics[$key] ?? 0);
    }

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    /**
     * [Description for calculateStrict]
     * 🚨 MODE STRICT
     *
     * @return array{score: int, rating: string}
     *
     * Created at: 23/03/2026 02:52:36 (America/Costa_Rica)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function calculateStrict(): array
    {
        if ($this->get('vulnerability_blocker') > 0) {
            return ['score' => 0, 'rating' => 'F'];
        }

        if ($this->get('bug_blocker') > 0) {
            return ['score' => 20, 'rating' => 'E'];
        }

        return $this->calculate();
    }
}
