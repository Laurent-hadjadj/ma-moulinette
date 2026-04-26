<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\UserAgentStatsRepository;
use App\Repository\UtilisateurRepository;
use App\Service\UserAgentReportingService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class UserAgentReportingServiceTest extends TestCase
{
    /** @var UserAgentStatsRepository&MockObject */
    private MockObject $statsRepo;

    /** @var UtilisateurRepository&MockObject */
    private MockObject $userRepo;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private UserAgentReportingService $service;

    protected function setUp(): void
    {
        $this->statsRepo = $this->createMock(UserAgentStatsRepository::class);
        $this->userRepo = $this->createMock(UtilisateurRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new UserAgentReportingService(
            $this->statsRepo,
            $this->logger,
            $this->userRepo
        );
    }

    // ─────────────────────────── getPeriodBounds ───────────────────────────

    public function testGetPeriodBoundsDayUsesTodayLabel(): void
    {
        $r = $this->service->getPeriodBounds('day');

        $this->assertSame('Aujourd’hui', $r['label']);
        $this->assertSame('00:00:00', $r['start']->format('H:i:s'));
        $this->assertSame('23:59:59', $r['end']->format('H:i:s'));
    }

    public function testGetPeriodBoundsWeekExplicitBuildsBoundsFromIsoWeek(): void
    {
        // 2026 - W17 = semaine du lundi 2026-04-20 au dimanche 2026-04-26
        $r = $this->service->getPeriodBounds('week', '2026-W17');

        $this->assertSame('2026-04-20', $r['start']->format('Y-m-d'));
        $this->assertSame('2026-04-26', $r['end']->format('Y-m-d'));
        $this->assertSame('Semaine du 20/04/2026 au 26/04/2026', $r['label']);
    }

    public function testGetPeriodBoundsMonthExplicitBuildsBoundsFromMonth(): void
    {
        $r = $this->service->getPeriodBounds('month', null, '2026-02');

        $this->assertSame('2026-02-01', $r['start']->format('Y-m-d'));
        $this->assertSame('2026-02-28', $r['end']->format('Y-m-d'));
        $this->assertSame('February 2026', $r['label']);
    }

    public function testGetPeriodBoundsInvalidPeriodThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid period');

        $this->service->getPeriodBounds('year');
    }

    // ─────────────────────────── Utilisateur aggregators ───────────────────────────

    public function testGetUtilisateurActifReturnsTotalFromRepository(): void
    {
        $this->userRepo->expects($this->once())
            ->method('countUtilisateurActif')
            ->willReturn(['code' => 200, 'total' => 42]);

        $this->assertSame(
            ['code' => 200, 'data' => ['nombre_utilisateur_actif' => 42]],
            $this->service->getUtilisateurActif()
        );
    }

    public function testGetUtilisateurActifPropagatesRepositoryErrorAndLogs(): void
    {
        $this->userRepo->expects($this->once())
            ->method('countUtilisateurActif')
            ->willReturn(['code' => 500, 'erreur' => 'DB down']);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[Statistique] ⚠️ Échec de la requête countUtilisateurActif().',
                $this->callback(fn (array $ctx) => $ctx['erreur'] === 'DB down')
            );

        $this->assertSame(
            ['code' => 500, 'erreur' => 'DB down'],
            $this->service->getUtilisateurActif()
        );
    }

    public function testGetUtilisateurDisponibleReturnsTotalFromRepository(): void
    {
        $this->userRepo->expects($this->once())
            ->method('countUtilisateurDisponible')
            ->willReturn(['code' => 200, 'total' => 100]);

        $this->assertSame(
            ['code' => 200, 'data' => ['nombre_utilisateur_disponible' => 100]],
            $this->service->getUtilisateurDisponible()
        );
    }

    public function testGetUtilisateurDisponibleLogsAndReturnsErrorOnRepositoryFailure(): void
    {
        $this->userRepo->expects($this->once())
            ->method('countUtilisateurDisponible')
            ->willReturn(['code' => 503]);

        $this->logger->expects($this->once())->method('warning');

        $this->assertSame(['code' => 503], $this->service->getUtilisateurDisponible());
    }

    // ─────────────────────────── Os/Browser/Device stats ───────────────────────────

    public function testGetOsStatsReturnsNormalizedStatsForFewItems(): void
    {
        $rows = [
            ['name' => 'Windows', 'version' => '11', 'total' => 60],
            ['name' => 'macOS',   'version' => '14', 'total' => 40],
        ];

        $this->statsRepo->expects($this->once())
            ->method('selectOsStatsByPeriod')
            ->willReturn(['code' => 200, 'liste' => $rows]);

        $result = $this->service->getOsStats($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'Avril');

        $this->assertSame(200, $result['code']);
        $this->assertSame('Avril', $result['period_label']);
        $this->assertSame(100, $result['data']['total']);
        $this->assertCount(2, $result['data']['items']);
        $this->assertSame(60.0, $result['data']['items'][0]['percent']);
        $this->assertSame(40.0, $result['data']['items'][1]['percent']);
    }

    public function testGetOsStatsBucketsOverflowIntoAutresWhenMoreThanFiveItems(): void
    {
        // 6 items → 5 mis dans items, le 6ᵉ bucketé dans "Autres"
        $rows = [
            ['name' => 'A', 'version' => '', 'total' => 30],
            ['name' => 'B', 'version' => '', 'total' => 25],
            ['name' => 'C', 'version' => '', 'total' => 20],
            ['name' => 'D', 'version' => '', 'total' => 10],
            ['name' => 'E', 'version' => '', 'total' => 8],
            ['name' => 'F', 'version' => '', 'total' => 7],
        ];

        $this->statsRepo->expects($this->once())
            ->method('selectOsStatsByPeriod')
            ->willReturn(['code' => 200, 'liste' => $rows]);

        $result = $this->service->getOsStats($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'Avril');

        $items = $result['data']['items'];
        $this->assertCount(6, $items); // 5 + bucket "Autres"
        $last = end($items);
        $this->assertSame('Autres', $last['name']);
        $this->assertSame(7, $last['total']);
        $this->assertSame(7.0, $last['percent']); // 7/100 = 7%
    }

    public function testGetOsStatsReturnsEmptyWhenTotalIsZero(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectOsStatsByPeriod')
            ->willReturn(['code' => 200, 'liste' => []]);

        $result = $this->service->getOsStats($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L');

        $this->assertSame(['total' => 0, 'items' => []], $result['data']);
    }

    public function testGetOsStatsPropagatesRepositoryError(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectOsStatsByPeriod')
            ->willReturn(['code' => 500, 'erreur' => 'x']);

        $this->logger->expects($this->once())->method('warning');

        $this->assertSame(
            ['code' => 500, 'erreur' => 'x'],
            $this->service->getOsStats($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L')
        );
    }

    public function testGetBrowserStatsDelegatesToBrowserRepositoryMethod(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectBrowserStatsByPeriod')
            ->willReturn(['code' => 200, 'liste' => [['name' => 'Chrome', 'version' => '120', 'total' => 100]]]);

        $result = $this->service->getBrowserStats($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L');

        $this->assertSame(200, $result['code']);
        $this->assertSame(100, $result['data']['total']);
    }

    public function testGetDeviceStatsDelegatesToDeviceRepositoryMethod(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectDeviceTypeStatsByPeriod')
            ->willReturn(['code' => 200, 'liste' => [['name' => 'desktop', 'version' => '', 'total' => 80]]]);

        $result = $this->service->getDeviceStats($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L');

        $this->assertSame(200, $result['code']);
        $this->assertSame(80, $result['data']['total']);
    }

    // ─────────────────────────── getSessionPagesReport ───────────────────────────

    public function testGetSessionPagesReportComputesKpiAndPercentPerPage(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionPagesStats')
            ->willReturn([
                'code' => 200,
                'kpi' => ['unique_users' => 50, 'page_views' => 200],
                'items' => [
                    ['label' => 'Accueil', 'url' => '/', 'total' => 80],
                    ['label' => 'Projet',  'url' => '/projet', 'total' => 40],
                ],
            ]);

        $result = $this->service->getSessionPagesReport($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L');

        $this->assertSame(200, $result['code']);
        $this->assertSame(50, $result['kpi']['unique_users']);
        $this->assertSame(200, $result['kpi']['page_views']);
        $this->assertSame(4.0, $result['kpi']['pages_per_session']); // 200/50
        $this->assertSame(40.0, $result['pages']['items'][0]['percent']); // 80/200
        $this->assertSame(20.0, $result['pages']['items'][1]['percent']);
    }

    public function testGetSessionPagesReportHandlesZeroPageViewsGracefully(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionPagesStats')
            ->willReturn([
                'code' => 200,
                'kpi' => ['unique_users' => 0, 'page_views' => 0],
                'items' => [['label' => 'x', 'url' => '/', 'total' => 0]],
            ]);

        $result = $this->service->getSessionPagesReport($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L');

        $this->assertSame(0, $result['kpi']['pages_per_session']); // division par zéro évitée
        $this->assertSame(0, $result['pages']['items'][0]['percent']);
    }

    public function testGetSessionPagesReportPropagatesRepositoryError(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionPagesStats')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);

        $this->logger->expects($this->once())->method('warning');

        $this->assertSame(
            ['code' => 500, 'erreur' => 'boom'],
            $this->service->getSessionPagesReport($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L')
        );
    }

    public function testGetSessionPagesReportCatchesExceptionAndReturns500(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionPagesStats')
            ->willThrowException(new \RuntimeException('DB gone'));

        $result = $this->service->getSessionPagesReport($this->dt('2026-04-01'), $this->dt('2026-04-30'), 'L');

        $this->assertSame(500, $result['code']);
        $this->assertSame('DB gone', $result['erreur']);
    }

    // ─────────────────────────── Avg/Unique session + category reports ───────────────────────────

    public function testGetAvgSessionDurationReportFormatsRowsForFrontend(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectAvgSessionDurationStats')
            ->willReturn([
                'code' => 200,
                'rows' => [
                    ['session_date' => '2026-04-20', 'avg_duration_minutes' => '5.5'],
                    ['session_date' => '2026-04-21', 'avg_duration_minutes' => '7.1'],
                ],
            ]);

        $result = $this->service->getAvgSessionDurationReport();

        $this->assertSame(200, $result['code']);
        $this->assertCount(2, $result['data']);
        $this->assertSame(['date' => '2026-04-20', 'avg' => 5.5], $result['data'][0]);
    }

    public function testGetAvgSessionDurationReportReturnsEmptyDataWhenNoRows(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectAvgSessionDurationStats')
            ->willReturn(['code' => 200, 'rows' => []]);

        $this->assertSame(['code' => 200, 'data' => []], $this->service->getAvgSessionDurationReport());
    }

    public function testGetAvgSessionDurationReportCatchesExceptionAndReturns500(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectAvgSessionDurationStats')
            ->willThrowException(new \RuntimeException('oops'));

        $result = $this->service->getAvgSessionDurationReport();

        $this->assertSame(500, $result['code']);
        $this->assertSame([], $result['data']);
        $this->assertSame('oops', $result['erreur']);
    }

    public function testGetUniqueSessionReportFormatsRowsForFrontend(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectUniqueSessionStats')
            ->willReturn([
                'code' => 200,
                'rows' => [['session_date' => '2026-04-20', 'nb_sessions' => '10']],
            ]);

        $result = $this->service->getUniqueSessionReport();

        $this->assertSame(200, $result['code']);
        $this->assertSame(['date' => '2026-04-20', 'session' => 10.0], $result['data'][0]);
    }

    public function testGetUniqueSessionReportReturnsEmptyDataWhenNoRows(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectUniqueSessionStats')
            ->willReturn(['code' => 200, 'rows' => []]);

        $this->assertSame(['code' => 200, 'data' => []], $this->service->getUniqueSessionReport());
    }

    public function testGetUniqueSessionByCategoryReportComputesPercentFromCount(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionDurationByCategoryStats')
            ->willReturn([
                'code' => 200,
                'rows' => [
                    [
                        'session_start' => '2026-04-20 10:00:00',
                        'session_end' => '2026-04-20 10:30:00',
                        'duration_minutes' => '30',
                        'duration_hours' => '0.5',
                        'session_length_category' => 'short',
                    ],
                    [
                        'session_start' => '2026-04-20 11:00:00',
                        'session_end' => '2026-04-20 12:00:00',
                        'duration_minutes' => '60',
                        'duration_hours' => '1',
                        'session_length_category' => 'long',
                    ],
                ],
            ]);

        $result = $this->service->getUniqueSessionByCategoryReport();

        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $result['items']);
        // 100/2 = 50
        $this->assertSame(50.0, $result['items'][0]['percent']);
        $this->assertSame('short', $result['items'][0]['category']);
    }

    public function testGetUniqueSessionByCategoryReportCatchesException(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionDurationByCategoryStats')
            ->willThrowException(new \RuntimeException('x'));

        $this->assertSame(
            ['code' => 500, 'erreur' => 'x'],
            $this->service->getUniqueSessionByCategoryReport()
        );
    }

    public function testGetCategoryByUniqueSessionReportComputesPercentAndCounts(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectCategoryByUniqueSessionStats')
            ->willReturn([
                'code' => 200,
                'rows' => [
                    ['category' => 'A', 'session_count' => 10, 'avg_duration_min' => '3.5'],
                    ['category' => 'B', 'session_count' => 5, 'avg_duration_min' => '2.1'],
                ],
            ]);

        $result = $this->service->getCategoryByUniqueSessionReport();

        $this->assertSame(2, $result['total']);
        $this->assertSame(50.0, $result['items'][0]['percent']);
        $this->assertSame(3.5, $result['items'][0]['average']);
    }

    public function testGetSessionDurationByPeriodStatsFormatsItems(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionDurationByPeriodStats')
            ->willReturn([
                'code' => 200,
                'liste' => [
                    [
                        'session_date' => '2026-04-20',
                        'avg_duration_minutes' => 10,
                        'avg_duration_hours' => 0.17,
                        'percent_of_total_avg' => 25,
                    ],
                ],
            ]);

        $result = $this->service->getSessionDurationByPeriodStats(
            $this->dt('2026-04-01'),
            $this->dt('2026-04-30'),
            'Avril'
        );

        $this->assertSame(1, $result['total']);
        $this->assertSame('Avril', $result['period_label']);
        $this->assertSame('2026-04-20', $result['items'][0]['date']);
        $this->assertSame(25, $result['items'][0]['percent']);
    }

    public function testGetSessionDurationByPeriodStatsCatchesException(): void
    {
        $this->statsRepo->expects($this->once())
            ->method('selectSessionDurationByPeriodStats')
            ->willThrowException(new \RuntimeException('kaboom'));

        $result = $this->service->getSessionDurationByPeriodStats(
            $this->dt('2026-04-01'),
            $this->dt('2026-04-30'),
            'L'
        );

        $this->assertSame(500, $result['code']);
        $this->assertSame('kaboom', $result['erreur']);
    }

    // ─────────────────────────── helpers ───────────────────────────

    private function dt(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date);
    }
}
