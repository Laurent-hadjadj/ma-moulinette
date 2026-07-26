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

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\RapportInsightService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RapportInsightService::class)]
final class RapportInsightServiceTest extends TestCase
{
    private RapportInsightService $svc;

    protected function setUp(): void
    {
        $this->svc = new RapportInsightService();
    }

    // =====================================================================
    // extractKpi()
    // =====================================================================

    public function testExtractKpiEmptyReturnsEmptyArray(): void
    {
        self::assertSame([], $this->svc->extractKpi([]));
    }

    public function testExtractKpiReturnsSixItems(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow()]);
        self::assertCount(6, $kpis);
    }

    public function testExtractKpiTrendIsNaWithSingleVersion(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow()]);
        foreach ($kpis as $kpi) {
            self::assertSame('na', $kpi['trend'], "KPI '{$kpi['label']}' — trend attendu 'na' sans version précédente");
        }
    }

    // --- Quality Gate KPI ---

    public function testExtractKpiQualityGateOkSeverity(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['alert_status' => 'OK'])]);
        $qg = $kpis[0];
        self::assertSame('Quality Gate', $qg['label']);
        self::assertSame('OK', $qg['value']);
        self::assertSame('ok', $qg['severity']);
    }

    public function testExtractKpiQualityGateWarnSeverity(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['alert_status' => 'WARN'])]);
        self::assertSame('warn', $kpis[0]['severity']);
    }

    public function testExtractKpiQualityGateErrorSeverity(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['alert_status' => 'ERROR'])]);
        self::assertSame('error', $kpis[0]['severity']);
    }

    public function testExtractKpiQualityGateNullSeverityIsNa(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['alert_status' => null])]);
        self::assertSame('na', $kpis[0]['severity']);
        self::assertNull($kpis[0]['value']);
    }

    public function testExtractKpiQualityGateTrendUpWhenImproved(): void
    {
        $suivi = [
            $this->makeRow(['alert_status' => 'ERROR']),
            $this->makeRow(['alert_status' => 'OK']),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        self::assertSame('up', $kpis[0]['trend']);
        self::assertSame('ERROR → OK', $kpis[0]['delta_label']);
    }

    public function testExtractKpiQualityGateTrendDownWhenDegraded(): void
    {
        $suivi = [
            $this->makeRow(['alert_status' => 'OK']),
            $this->makeRow(['alert_status' => 'ERROR']),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        self::assertSame('down', $kpis[0]['trend']);
    }

    public function testExtractKpiQualityGateTrendFlatWhenSame(): void
    {
        $suivi = [
            $this->makeRow(['alert_status' => 'OK']),
            $this->makeRow(['alert_status' => 'OK']),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        self::assertSame('flat', $kpis[0]['trend']);
        self::assertNull($kpis[0]['delta_label']);
    }

    // --- Couverture KPI ---

    #[DataProvider('coverageSeverityProvider')]
    public function testExtractKpiCoverageSeverity(string $coverage, string $expectedSeverity): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['coverage' => $coverage])]);
        $cov = $kpis[1];
        self::assertSame('Couverture', $cov['label']);
        self::assertSame($expectedSeverity, $cov['severity']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function coverageSeverityProvider(): array
    {
        return [
            'above 80 = ok'          => ['85.0', 'ok'],
            'exactly 80 = ok'        => ['80.0', 'ok'],
            'between 50 and 80 = warn' => ['65.0', 'warn'],
            'exactly 50 = warn'      => ['50.0', 'warn'],
            'below 50 = error'       => ['40.0', 'error'],
        ];
    }

    public function testExtractKpiCoverageDeltaPositive(): void
    {
        $suivi = [
            $this->makeRow(['coverage' => '70.0']),
            $this->makeRow(['coverage' => '80.0']),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        $cov = $kpis[1];
        self::assertSame('up', $cov['trend']);
        self::assertStringContainsString('+', (string) $cov['delta_label']);
    }

    public function testExtractKpiCoverageDeltaNegative(): void
    {
        $suivi = [
            $this->makeRow(['coverage' => '80.0']),
            $this->makeRow(['coverage' => '70.0']),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        self::assertSame('down', $kpis[1]['trend']);
    }

    // --- Bugs KPI (lower is better) ---

    public function testExtractKpiBugsNoteAIsOk(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['bug' => 0, 'reliability' => 'A'])]);
        $bugs = $kpis[2];
        self::assertSame('Bugs', $bugs['label']);
        self::assertSame('ok', $bugs['severity']);
        self::assertSame('A', $bugs['note']);
    }

    public function testExtractKpiBugsNoteDIsError(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['bug' => 10, 'reliability' => 'D'])]);
        self::assertSame('error', $kpis[2]['severity']);
    }

    public function testExtractKpiBugsTrendUpWhenCountDecreased(): void
    {
        $suivi = [
            $this->makeRow(['bug' => 10]),
            $this->makeRow(['bug' => 5]),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        self::assertSame('up', $kpis[2]['trend']);
    }

    public function testExtractKpiBugsTrendDownWhenCountIncreased(): void
    {
        $suivi = [
            $this->makeRow(['bug' => 5]),
            $this->makeRow(['bug' => 10]),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        self::assertSame('down', $kpis[2]['trend']);
    }

    public function testExtractKpiBugsTrendFlatWhenUnchanged(): void
    {
        $suivi = [
            $this->makeRow(['bug' => 5]),
            $this->makeRow(['bug' => 5]),
        ];
        $kpis = $this->svc->extractKpi($suivi);
        self::assertSame('flat', $kpis[2]['trend']);
    }

    // --- Hotspots KPI ---

    public function testExtractKpiHotspotsMinusOneValueIsNull(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['menace_potentielle_totale' => -1, 'note_hotspot' => null])]);
        $hot = $kpis[4];
        self::assertSame('Hotspots', $hot['label']);
        self::assertNull($hot['value']);
        self::assertSame('na', $hot['severity']);
    }

    public function testExtractKpiHotspotsNoteAIsOk(): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['menace_potentielle_totale' => 0, 'note_hotspot' => 'A'])]);
        self::assertSame('ok', $kpis[4]['severity']);
    }

    // --- SeverityFromNote via DataProvider ---

    #[DataProvider('noteSeverityProvider')]
    public function testExtractKpiBugsSeverityFromNote(string $note, string $expectedSeverity): void
    {
        $kpis = $this->svc->extractKpi([$this->makeRow(['reliability' => $note])]);
        self::assertSame($expectedSeverity, $kpis[2]['severity']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function noteSeverityProvider(): array
    {
        return [
            'A = ok'    => ['A', 'ok'],
            'B = ok'    => ['B', 'ok'],
            'C = warn'  => ['C', 'warn'],
            'D = error' => ['D', 'error'],
            'E = error' => ['E', 'error'],
        ];
    }

    // =====================================================================
    // extractTopAlerts()
    // =====================================================================

    public function testExtractTopAlertsEmptyReturnsEmpty(): void
    {
        self::assertSame([], $this->svc->extractTopAlerts([]));
    }

    public function testExtractTopAlertsQualityGateErrorGeneratesAlert(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['alert_status' => 'ERROR'])]);
        $titles = array_column($alerts, 'titre');
        self::assertContains('Quality Gate en échec', $titles);
        $idx = array_search('Quality Gate en échec', $titles);
        self::assertSame('error', $alerts[$idx]['severity']);
    }

    public function testExtractTopAlertsQualityGateOkNoAlert(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['alert_status' => 'OK'])]);
        self::assertNotContains('Quality Gate en échec', array_column($alerts, 'titre'));
    }

    public function testExtractTopAlertsNoteEIsErrorSeverity(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['reliability' => 'E'])]);
        $found = false;
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'Fiabilité')) {
                $found = true;
                self::assertSame('error', $a['severity']);
            }
        }
        self::assertTrue($found, 'Alerte Fiabilité note E attendue');
    }

    public function testExtractTopAlertsNoteDIsWarnSeverity(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['reliability' => 'D'])]);
        $found = false;
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'Fiabilité')) {
                $found = true;
                self::assertSame('warn', $a['severity']);
            }
        }
        self::assertTrue($found, 'Alerte Fiabilité note D attendue');
    }

    public function testExtractTopAlertsNoteAProducesNoAlert(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['reliability' => 'A'])]);
        $fiabiliteAlerts = array_filter($alerts, fn(array $a): bool => str_contains($a['titre'], 'Fiabilité'));
        self::assertEmpty($fiabiliteAlerts, "Pas d'alerte Fiabilité attendue pour note A");
    }

    public function testExtractTopAlertsCoverageBelowCriticalThresholdIsError(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['coverage' => '25.0'])]);
        $found = false;
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'Couverture faible')) {
                $found = true;
                self::assertSame('error', $a['severity']);
            }
        }
        self::assertTrue($found, 'Alerte couverture < 30 % attendue en error');
    }

    public function testExtractTopAlertsCoverageBetween30And50IsWarn(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['coverage' => '40.0'])]);
        $found = false;
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'Couverture faible')) {
                $found = true;
                self::assertSame('warn', $a['severity']);
            }
        }
        self::assertTrue($found, 'Alerte couverture 30-50 % attendue en warn');
    }

    public function testExtractTopAlertsCoverageAbove50NoAlert(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['coverage' => '75.0'])]);
        $covAlerts = array_filter($alerts, fn(array $a): bool => str_contains($a['titre'], 'Couverture faible'));
        self::assertEmpty($covAlerts, "Pas d'alerte 'Couverture faible' au-dessus de 50 %");
    }

    public function testExtractTopAlertsCoverageStrongDeclineOf20Pts(): void
    {
        $suivi = [
            $this->makeRow(['coverage' => '80.0']),
            $this->makeRow(['coverage' => '60.0']),
        ];
        $alerts = $this->svc->extractTopAlerts($suivi);
        self::assertContains('Couverture en forte baisse', array_column($alerts, 'titre'));
    }

    public function testExtractTopAlertsCoverageDeclineBelow10PtsNoAlert(): void
    {
        $suivi = [
            $this->makeRow(['coverage' => '80.0']),
            $this->makeRow(['coverage' => '75.0']),
        ];
        $alerts = $this->svc->extractTopAlerts($suivi);
        self::assertNotContains('Couverture en forte baisse', array_column($alerts, 'titre'));
    }

    public function testExtractTopAlertsTestErrorsAndFailures(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['test_errors' => 2, 'test_failures' => 1])]);
        $titles = array_column($alerts, 'titre');
        self::assertContains('Tests en échec : 3', $titles);
        $idx = array_search('Tests en échec : 3', $titles);
        self::assertSame('error', $alerts[$idx]['severity']);
    }

    public function testExtractTopAlertsNoTestErrorsNoAlert(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['test_errors' => 0, 'test_failures' => 0])]);
        $testAlerts = array_filter($alerts, fn(array $a): bool => str_contains($a['titre'], 'Tests en échec'));
        self::assertEmpty($testAlerts, "Pas d'alerte 'Tests en échec' quand 0 erreur");
    }

    public function testExtractTopAlertsHotspotsAbove50IsError(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['menace_potentielle_totale' => 50])]);
        $found = false;
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'hotspots')) {
                $found = true;
                self::assertSame('error', $a['severity']);
            }
        }
        self::assertTrue($found, 'Alerte hotspots >= 50 attendue en error');
    }

    public function testExtractTopAlertsHotspotsBetween10And49IsWarn(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['menace_potentielle_totale' => 15])]);
        $found = false;
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'hotspots')) {
                $found = true;
                self::assertSame('warn', $a['severity']);
            }
        }
        self::assertTrue($found, 'Alerte hotspots 10-49 attendue en warn');
    }

    public function testExtractTopAlertsHotspotsBelow10NoAlert(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['menace_potentielle_totale' => 5])]);
        $hotAlerts = array_filter($alerts, fn(array $a): bool => str_contains($a['titre'], 'hotspots'));
        self::assertEmpty($hotAlerts, "Pas d'alerte hotspots quand < 10");
    }

    public function testExtractTopAlertsComplexityDegradationAbove5Pts(): void
    {
        $suivi = [
            $this->makeRow(['complexity_ratio' => '10.0']),
            $this->makeRow(['complexity_ratio' => '18.0']),
        ];
        $alerts = $this->svc->extractTopAlerts($suivi);
        self::assertContains('Complexité cyclomatique en hausse', array_column($alerts, 'titre'));
    }

    public function testExtractTopAlertsComplexityDegradationBelow5PtsNoAlert(): void
    {
        $suivi = [
            $this->makeRow(['complexity_ratio' => '10.0']),
            $this->makeRow(['complexity_ratio' => '13.0']),
        ];
        $alerts = $this->svc->extractTopAlerts($suivi);
        self::assertNotContains('Complexité cyclomatique en hausse', array_column($alerts, 'titre'));
    }

    public function testExtractTopAlertsComplexityWithSingleVersionNoAlert(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['complexity_ratio' => '20.0'])]);
        self::assertNotContains('Complexité cyclomatique en hausse', array_column($alerts, 'titre'));
    }

    public function testExtractTopAlertsLimitIsRespected(): void
    {
        $suivi = [$this->makeRow([
            'alert_status' => 'ERROR',
            'reliability' => 'E',
            'security' => 'E',
            'maintainability' => 'E',
            'coverage' => '25.0',
            'test_errors' => 5,
            'menace_potentielle_totale' => 60,
        ])];
        $alerts = $this->svc->extractTopAlerts($suivi, 3);
        self::assertCount(3, $alerts);
    }

    public function testExtractTopAlertsSortedByScoreDescQualityGateFirst(): void
    {
        $suivi = [$this->makeRow([
            'alert_status' => 'ERROR',
            'reliability' => 'E',
            'coverage' => '25.0',
        ])];
        $alerts = $this->svc->extractTopAlerts($suivi);
        self::assertSame('Quality Gate en échec', $alerts[0]['titre'], 'QG (score 100) doit être premier');
    }

    public function testExtractTopAlertsScoreKeyRemovedFromResult(): void
    {
        $alerts = $this->svc->extractTopAlerts([$this->makeRow(['alert_status' => 'ERROR'])]);
        foreach ($alerts as $a) {
            self::assertArrayNotHasKey('score', $a);
        }
    }

    public function testExtractTopAlertsNotePhraseContainsDeltaWhenChanged(): void
    {
        $suivi = [
            $this->makeRow(['reliability' => 'A']),
            $this->makeRow(['reliability' => 'E']),
        ];
        $alerts = $this->svc->extractTopAlerts($suivi);
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'Fiabilité')) {
                self::assertStringContainsString('passé de A à E', $a['phrase']);
                return;
            }
        }
        self::fail('Alerte Fiabilité non trouvée');
    }

    public function testExtractTopAlertsNotePhraseContainsStableWhenUnchanged(): void
    {
        $suivi = [
            $this->makeRow(['reliability' => 'E']),
            $this->makeRow(['reliability' => 'E']),
        ];
        $alerts = $this->svc->extractTopAlerts($suivi);
        foreach ($alerts as $a) {
            if (str_contains($a['titre'], 'Fiabilité')) {
                self::assertStringContainsString('stable', $a['phrase']);
                return;
            }
        }
        self::fail('Alerte Fiabilité non trouvée');
    }

    // ---- helper ----

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function makeRow(array $overrides = []): array
    {
        return array_merge([
            'version'                    => '1.0.0',
            'date'                       => '2026-01-01',
            'alert_status'               => 'OK',
            'coverage'                   => '80.0',
            'bug'                        => 0,
            'reliability'                => 'A',
            'faille'                     => 0,
            'security'                   => 'A',
            'menace_potentielle_totale'  => 0,
            'note_hotspot'               => 'A',
            'mauvaise_pratique'          => 0,
            'maintainability'            => 'A',
            'note_complexity'            => null,
            'note_cognitive'             => null,
            'test_errors'                => 0,
            'test_failures'              => 0,
            'complexity_ratio'           => null,
        ], $overrides);
    }
}
