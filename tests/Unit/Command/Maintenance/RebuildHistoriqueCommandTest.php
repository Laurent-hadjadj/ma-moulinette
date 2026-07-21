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

namespace App\Tests\Unit\Command\Maintenance;

use App\Command\Maintenance\RebuildHistoriqueCommand;
use App\Exception\SonarApiException;
use App\Repository\HistoriqueRepository;
use App\Service\CommandRebuildHistorique\{BuildMapHistoryService, SonarAnalysisFetcherService, SonarMetricsFetcherService};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[AllowMockObjectsWithoutExpectations]
class RebuildHistoriqueCommandTest extends TestCase
{
    /** @var SonarAnalysisFetcherService&\PHPUnit\Framework\MockObject\MockObject */
    private $analysisFetcher;
    /** @var SonarMetricsFetcherService&\PHPUnit\Framework\MockObject\MockObject */
    private $metricsFetcher;
    /** @var BuildMapHistoryService&\PHPUnit\Framework\MockObject\MockObject */
    private $buildMapHistory;
    /** @var HistoriqueRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $historiqueRepos;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->analysisFetcher = $this->createMock(SonarAnalysisFetcherService::class);
        $this->metricsFetcher = $this->createMock(SonarMetricsFetcherService::class);
        $this->buildMapHistory = $this->createMock(BuildMapHistoryService::class);
        $this->historiqueRepos = $this->createMock(HistoriqueRepository::class);

        $cmd = new RebuildHistoriqueCommand(
            $this->analysisFetcher,
            $this->metricsFetcher,
            $this->buildMapHistory,
            $this->historiqueRepos,
        );
        $this->tester = new CommandTester($cmd);
    }

    private function stubTwoAnalyses(): void
    {
        $analyses = [
            ['analysisKey' => 'AK-1', 'version' => '1.0.0'],
            ['analysisKey' => 'AK-2', 'version' => '1.1.0'],
        ];
        $this->analysisFetcher->method('fetchLatestAnalysesPerVersion')->willReturn($analyses);
        $this->analysisFetcher->method('computeVersionCounters')->willReturn($analyses);
        $this->metricsFetcher->method('fetchMetrics')->willReturn(['metric' => 'value']);
        $this->buildMapHistory->method('metricsRebuild')->willReturnCallback(
            fn(array $metrics, array $analysis) => ['version' => $analysis['version']]
        );
    }

    public function testMissingProjectOptionReturnsFailure(): void
    {
        $exit = $this->tester->execute([]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('--project obligatoire', $this->tester->getDisplay());
    }

    public function testNoAnalysesReturnsSuccessWithWarning(): void
    {
        $this->analysisFetcher->method('fetchLatestAnalysesPerVersion')->willReturn([]);
        $this->analysisFetcher->method('computeVersionCounters')->willReturn([]);

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Aucune donnée historique', $this->tester->getDisplay());
    }

    public function testDryRunNeverCallsInsert(): void
    {
        $this->stubTwoAnalyses();
        $this->historiqueRepos->expects($this->never())->method('insertHistoriqueAjoutProjet');

        $exit = $this->tester->execute(['--project' => 'mon-projet', '--dry-run' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Simulation terminée', $this->tester->getDisplay());
    }

    public function testRealRunCallsInsertOncePerAnalysis(): void
    {
        $this->stubTwoAnalyses();
        $this->historiqueRepos->expects($this->exactly(2))
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->callback(static fn($v) => is_array($v)), [])
            ->willReturn(['code' => 200, 'erreur' => '']);

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('2 ligne(s) insérée(s)', $this->tester->getDisplay());
    }

    public function testInsertFailureIsReportedAndReturnsFailureCode(): void
    {
        $this->stubTwoAnalyses();
        $this->historiqueRepos->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(1, $exit, 'Command::FAILURE = 1');
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('boom', $output);
        $this->assertStringContainsString('2 échec(s)', $output);
    }

    public function testSonarApiExceptionOnOneAnalysisIsSkippedNotFatal(): void
    {
        $this->stubTwoAnalyses();
        $this->metricsFetcher->method('fetchMetrics')
            ->willThrowException(new SonarApiException('indisponible', []));
        $this->historiqueRepos->expects($this->never())->method('insertHistoriqueAjoutProjet');

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        // Aucune ligne insérée (toutes les analyses ont échoué en amont) mais pas de crash.
        $this->assertSame(0, $exit);
        $this->assertStringContainsString('indisponible', $this->tester->getDisplay());
    }
}
