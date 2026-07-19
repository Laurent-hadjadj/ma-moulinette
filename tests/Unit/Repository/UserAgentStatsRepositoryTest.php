<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\UserAgentAnalysis;
use App\Repository\UserAgentStatsRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-06-09 : tests Unit UserAgentStatsRepository.
 * Couvre les 7 méthodes select* et handleDatabaseException (3 cas).
 * selectSessionPagesStats (2 prepare consécutifs) a son propre builder.
 */
#[AllowMockObjectsWithoutExpectations]
class UserAgentStatsRepositoryTest extends TestCase
{
    /** Crée un repo dont prepare() renvoie toujours la même réponse (1 seul prepare). */
    private function buildRepo(array|\Throwable $rowsOrException): UserAgentStatsRepository
    {
        $connection = $this->createStub(Connection::class);

        if ($rowsOrException instanceof \Throwable) {
            $connection->method('prepare')->willThrowException($rowsOrException);
        } else {
            $result    = $this->createStub(Result::class);
            $result->method('fetchAllAssociative')->willReturn($rowsOrException);
            $stmt      = $this->createStub(Statement::class);
            $stmt->method('executeQuery')->willReturn($result);
            $connection->method('prepare')->willReturn($stmt);
        }

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(UserAgentAnalysis::class));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return new UserAgentStatsRepository($registry);
    }

    /** Crée un repo avec deux résultats distincts pour deux prepare() consécutifs. */
    private function buildRepoTwoPrepares(
        array $firstResult,
        bool $firstIsAssoc,
        array $secondResult
    ): UserAgentStatsRepository {
        $res1 = $this->createStub(Result::class);
        if ($firstIsAssoc) {
            $res1->method('fetchAssociative')->willReturn($firstResult);
        } else {
            $res1->method('fetchAllAssociative')->willReturn($firstResult);
        }
        $stmt1 = $this->createStub(Statement::class);
        $stmt1->method('executeQuery')->willReturn($res1);

        $res2 = $this->createStub(Result::class);
        $res2->method('fetchAllAssociative')->willReturn($secondResult);
        $stmt2 = $this->createStub(Statement::class);
        $stmt2->method('executeQuery')->willReturn($res2);

        $connection = $this->createStub(Connection::class);
        $connection->method('prepare')->willReturnOnConsecutiveCalls($stmt1, $stmt2);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(UserAgentAnalysis::class));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return new UserAgentStatsRepository($registry);
    }

    private function makeDates(): array
    {
        return [
            new \DateTimeImmutable('2026-06-01 00:00:00'),
            new \DateTimeImmutable('2026-06-07 23:59:59'),
        ];
    }

    // ──── handleDatabaseException ─────────────────────────────────────────────

    public function testHandleDatabaseExceptionGenericMessage(): void
    {
        $repo = $this->buildRepo([]);
        $r = $repo->handleDatabaseException(new \RuntimeException('boom'));
        $this->assertSame(500, $r['code']);
        $this->assertSame('boom', $r['erreur']);
    }

    public function testHandleDatabaseExceptionConnectionException(): void
    {
        $repo     = $this->buildRepo([]);
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $e        = new ConnectionException($driverEx, null);
        $r        = $repo->handleDatabaseException($e);
        $this->assertSame(500, $r['code']);
        $this->assertSame(UserAgentStatsRepository::$noDataBase, $r['erreur']);
    }

    public function testHandleDatabaseExceptionUniqueViolation(): void
    {
        $repo     = $this->buildRepo([]);
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $e        = new UniqueConstraintViolationException($driverEx, null);
        $r        = $repo->handleDatabaseException($e);
        $this->assertSame(23505, $r['code']);
        $this->assertSame('Les informations existent déjà.', $r['erreur']);
    }

    // ──── selectDeviceTypeStatsByPeriod ───────────────────────────────────────

    public function testSelectDeviceTypeStatsByPeriodReturnsRows(): void
    {
        $rows = [
            ['name' => 'desktop',    'total' => '42'],
            ['name' => 'smartphone', 'total' => '15'],
        ];
        [$start, $end] = $this->makeDates();
        $repo   = $this->buildRepo($rows);
        $result = $repo->selectDeviceTypeStatsByPeriod($start, $end);
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['liste']);
    }

    public function testSelectDeviceTypeStatsByPeriodReturnsEmptyList(): void
    {
        [$start, $end] = $this->makeDates();
        $repo   = $this->buildRepo([]);
        $result = $repo->selectDeviceTypeStatsByPeriod($start, $end);
        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testSelectDeviceTypeStatsByPeriodReturnsErrorOnException(): void
    {
        [$start, $end] = $this->makeDates();
        $repo   = $this->buildRepo(new \RuntimeException('device fail'));
        $result = $repo->selectDeviceTypeStatsByPeriod($start, $end);
        $this->assertSame(500, $result['code']);
        $this->assertSame('device fail', $result['erreur']);
    }

    // ──── selectOsStatsByPeriod ───────────────────────────────────────────────

    public function testSelectOsStatsByPeriodReturnsRows(): void
    {
        $rows = [['name' => 'Windows', 'version' => '11', 'total' => '5']];
        [$start, $end] = $this->makeDates();
        $result = $this->buildRepo($rows)->selectOsStatsByPeriod($start, $end);
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['liste']);
    }

    public function testSelectOsStatsByPeriodReturnsErrorOnException(): void
    {
        [$start, $end] = $this->makeDates();
        $result = $this->buildRepo(new \RuntimeException('os fail'))->selectOsStatsByPeriod($start, $end);
        $this->assertSame(500, $result['code']);
    }

    // ──── selectBrowserStatsByPeriod ──────────────────────────────────────────

    public function testSelectBrowserStatsByPeriodReturnsRows(): void
    {
        $rows = [['name' => 'Chrome', 'version' => '125.0', 'total' => '10']];
        [$start, $end] = $this->makeDates();
        $result = $this->buildRepo($rows)->selectBrowserStatsByPeriod($start, $end);
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['liste']);
    }

    public function testSelectBrowserStatsByPeriodReturnsErrorOnException(): void
    {
        [$start, $end] = $this->makeDates();
        $result = $this->buildRepo(new \RuntimeException('browser fail'))->selectBrowserStatsByPeriod($start, $end);
        $this->assertSame(500, $result['code']);
    }

    // ──── selectSessionPagesStats ─────────────────────────────────────────────

    public function testSelectSessionPagesStatsReturnsBothKpiAndItems(): void
    {
        $kpi   = ['unique_users' => 3, 'page_views' => 10];
        $items = [['label' => 'ACCUEIL', 'url' => '/accueil', 'total' => 5]];
        [$start, $end] = $this->makeDates();

        $repo   = $this->buildRepoTwoPrepares($kpi, true, $items);
        $result = $repo->selectSessionPagesStats($start, $end);

        $this->assertSame(200, $result['code']);
        $this->assertSame($kpi,   $result['kpi']);
        $this->assertSame($items, $result['items']);
    }

    public function testSelectSessionPagesStatsReturnsErrorOnException(): void
    {
        [$start, $end] = $this->makeDates();
        $result = $this->buildRepo(new \RuntimeException('pages fail'))->selectSessionPagesStats($start, $end);
        $this->assertSame(500, $result['code']);
    }

    // ──── selectAvgSessionDurationStats ──────────────────────────────────────

    public function testSelectAvgSessionDurationStatsReturnsRows(): void
    {
        $rows = [['session_date' => '2026-06-09', 'avg_duration_minutes' => '12.50']];
        $result = $this->buildRepo($rows)->selectAvgSessionDurationStats();
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['rows']);
        $this->assertSame('', $result['erreur']);
    }

    public function testSelectAvgSessionDurationStatsReturnsErrorOnException(): void
    {
        $result = $this->buildRepo(new \RuntimeException('avg fail'))->selectAvgSessionDurationStats();
        $this->assertSame(500, $result['code']);
    }

    // ──── selectUniqueSessionStats ────────────────────────────────────────────

    public function testSelectUniqueSessionStatsReturnsRows(): void
    {
        $rows = [['session_date' => '2026-06-09', 'nb_sessions' => '3']];
        $result = $this->buildRepo($rows)->selectUniqueSessionStats();
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['rows']);
    }

    public function testSelectUniqueSessionStatsReturnsErrorOnException(): void
    {
        $result = $this->buildRepo(new \RuntimeException('unique fail'))->selectUniqueSessionStats();
        $this->assertSame(500, $result['code']);
    }

    // ──── selectSessionDurationByCategoryStats ────────────────────────────────

    public function testSelectSessionDurationByCategoryStatsReturnsRows(): void
    {
        $rows = [['session_id' => 'abc', 'duration_minutes' => '15.50', 'session_length_category' => 'moyen']];
        $result = $this->buildRepo($rows)->selectSessionDurationByCategoryStats(...$this->makeDates());
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['rows']);
    }

    public function testSelectSessionDurationByCategoryStatsReturnsErrorOnException(): void
    {
        $result = $this->buildRepo(new \RuntimeException('cat fail'))
            ->selectSessionDurationByCategoryStats(...$this->makeDates());
        $this->assertSame(500, $result['code']);
    }

    // ──── selectCategoryByUniqueSessionStats ──────────────────────────────────

    public function testSelectCategoryByUniqueSessionStatsReturnsRows(): void
    {
        $rows = [['category' => 'Moyen', 'session_count' => '5', 'percentage' => '50']];
        $result = $this->buildRepo($rows)->selectCategoryByUniqueSessionStats(...$this->makeDates());
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['rows']);
    }

    public function testSelectCategoryByUniqueSessionStatsReturnsErrorOnException(): void
    {
        $result = $this->buildRepo(new \RuntimeException('cat2 fail'))
            ->selectCategoryByUniqueSessionStats(...$this->makeDates());
        $this->assertSame(500, $result['code']);
    }

    // ──── selectSessionDurationByPeriodStats ──────────────────────────────────

    public function testSelectSessionDurationByPeriodStatsReturnsRows(): void
    {
        $rows = [[
            'session_date'          => '2026-06-09',
            'avg_duration_minutes'  => '10.00',
            'avg_duration_hours'    => '0.17',
            'percent_of_total_avg'  => '100.00',
        ]];
        [$start, $end] = $this->makeDates();
        $result = $this->buildRepo($rows)->selectSessionDurationByPeriodStats($start, $end);
        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['liste']);
        $this->assertSame('', $result['erreur']);
    }

    public function testSelectSessionDurationByPeriodStatsReturnsErrorOnException(): void
    {
        [$start, $end] = $this->makeDates();
        $result = $this->buildRepo(new \RuntimeException('period fail'))->selectSessionDurationByPeriodStats($start, $end);
        $this->assertSame(500, $result['code']);
    }
}
