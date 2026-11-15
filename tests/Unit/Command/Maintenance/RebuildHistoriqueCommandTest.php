<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Command\Maintenance;

use App\Command\Maintenance\RebuildHistoriqueCommand;
use App\Exception\SonarApiException;
use App\Repository\HistoriqueRepository;
use App\Service\CommandRebuildHistorique\{BuildMapHistoryService, SonarAnalysisFetcherService, SonarMetricsFetcherService};
use App\Service\GetSonarVersionService;
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
    /** @var GetSonarVersionService&\PHPUnit\Framework\MockObject\MockObject */
    private $sonarVersion;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->analysisFetcher = $this->createMock(SonarAnalysisFetcherService::class);
        $this->metricsFetcher = $this->createMock(SonarMetricsFetcherService::class);
        $this->buildMapHistory = $this->createMock(BuildMapHistoryService::class);
        $this->historiqueRepos = $this->createMock(HistoriqueRepository::class);
        $this->sonarVersion = $this->createMock(GetSonarVersionService::class);
        $this->sonarVersion->method('getSonarQubeVersion')->willReturn('26.11.0.134175');

        $cmd = new RebuildHistoriqueCommand(
            $this->analysisFetcher,
            $this->metricsFetcher,
            $this->buildMapHistory,
            $this->historiqueRepos,
            $this->sonarVersion,
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
    /**
     * Non-régression : une version déjà présente (code 23505, ex. enregistrée par la collecte qui précède) n'est pas
     * un échec. Avant correction, elle faisait retourner FAILURE et masquait les vraies erreurs.
     */
    public function testVersionDejaPresenteNEstPasUnEchec(): void
    {
        $this->stubTwoAnalyses();
        $this->historiqueRepos->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 23505, 'erreur' => 'Les informations existent déjà.']);

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(0, $exit);
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('0 ligne(s) insérée(s), 2 déjà présente(s)', $output);
        $this->assertStringNotContainsString('échec', $output);
    }

    public function testVersionDejaPresenteEtVraieErreurRestentDistinguees(): void
    {
        $this->stubTwoAnalyses();
        $this->historiqueRepos->method('insertHistoriqueAjoutProjet')
            ->willReturnOnConsecutiveCalls(
                ['code' => 23505, 'erreur' => 'existe déjà'],
                ['code' => 500, 'erreur' => 'boom']
            );

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('0 ligne(s) insérée(s), 1 déjà présente(s), 1 échec(s)', $this->tester->getDisplay());
    }

    /**
     * Non-régression : la carte de reconstruction n'a pas `sonarqube_server_version` (colonne NOT NULL de `historique`) ;
     * l'insertion échouait en SQLSTATE 23502. La commande doit la compléter.
     */
    public function testLaVersionDuServeurSonarQubeEstAjouteeALaCarteInseree(): void
    {
        $this->stubTwoAnalyses();
        $recues = [];
        $this->historiqueRepos->method('insertHistoriqueAjoutProjet')->willReturnCallback(
            function (array $map) use (&$recues): array {
                $recues[] = $map['sonarqube_server_version'] ?? '(absente)';
                return ['code' => 200, 'erreur' => ''];
            }
        );

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(0, $exit);
        $this->assertSame(['26.11.0.134175', '26.11.0.134175'], $recues);
    }

    public function testLaVersionDuServeurInconnueDonneInconnuEtNeBloquePas(): void
    {
        $sonar = $this->createMock(GetSonarVersionService::class);
        $sonar->method('getSonarQubeVersion')->willReturn(['code' => 503]);
        $cmd = new RebuildHistoriqueCommand(
            $this->analysisFetcher,
            $this->metricsFetcher,
            $this->buildMapHistory,
            $this->historiqueRepos,
            $sonar,
        );
        $this->stubTwoAnalyses();
        $recues = [];
        $this->historiqueRepos->method('insertHistoriqueAjoutProjet')->willReturnCallback(
            function (array $map) use (&$recues): array {
                $recues[] = $map['sonarqube_server_version'] ?? '(absente)';
                return ['code' => 200, 'erreur' => ''];
            }
        );

        $exit = (new CommandTester($cmd))->execute(['--project' => 'mon-projet']);

        $this->assertSame(0, $exit);
        $this->assertSame(['inconnu', 'inconnu'], $recues);
    }

    /**
     * Non-régression : la même analyse déjà en base (collecte en UTC, reconstruction en heure locale, version
     * « not provided » non dé-dupliquée par version) créait une 2e ligne d'historique pour une seule analyse.
     */
    public function testUneAnalyseDejaEnBaseNEstPasReinseree(): void
    {
        $this->stubTwoAnalyses();
        $this->historiqueRepos->method('analyseExiste')->willReturn(true);
        $this->historiqueRepos->expects($this->never())->method('insertHistoriqueAjoutProjet');

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('0 ligne(s) insérée(s), 2 déjà présente(s)', $this->tester->getDisplay());
    }

    public function testSeuleLAnalyseAbsenteDeLaBaseEstInseree(): void
    {
        $this->stubTwoAnalyses();
        $this->historiqueRepos->method('analyseExiste')->willReturnOnConsecutiveCalls(true, false);
        $this->historiqueRepos->expects($this->once())->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 200, 'erreur' => '']);

        $exit = $this->tester->execute(['--project' => 'mon-projet']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('1 ligne(s) insérée(s), 1 déjà présente(s)', $this->tester->getDisplay());
    }
}
