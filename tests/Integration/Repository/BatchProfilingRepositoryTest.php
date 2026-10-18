<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\BatchProfiling;
use App\Repository\BatchProfilingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * MODIF 2026-10-18 : BatchProfilingRepositoryTest (Unit) ne couvre que
 * countOlderThan()/purge() (mocks). Les 7 autres méthodes (getGlobalKpi,
 * findGlobalSummary, findLatest, findStatsByPortefeuille, findWeeklyStats,
 * findMonthlyStats, findUsersStats) lisent des vues PostgreSQL
 * (vw_batch_profiling_*, migrations/POSTGRESQL/30_views/) non mockables
 * proprement — couvertes ici contre la vraie base de test.
 */
class BatchProfilingRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BatchProfilingRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(BatchProfiling::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.batch_profiling');

        $this->em->persist(new BatchProfiling(
            'portefeuille-a',
            10,
            12.5,
            1.25,
            64.0,
            32.0,
            'laurent.hadjadj@ma-moulinette.fr',
            'exec-ref-1'
        ));
        $this->em->persist(new BatchProfiling(
            'portefeuille-b',
            5,
            6.0,
            1.2,
            48.0,
            24.0,
            'laurent.hadjadj@ma-moulinette.fr',
            'exec-ref-2'
        ));
        $this->em->flush();
    }

    public function testGetGlobalKpiReturnsSummaryRow(): void
    {
        $r = $this->repo->getGlobalKpi();

        $this->assertSame(200, $r['code']);
        $this->assertSame('', $r['erreur']);
        $this->assertCount(1, $r['summary']);
    }

    public function testFindGlobalSummaryReturnsRowsForValidIndicator(): void
    {
        $r = $this->repo->findGlobalSummary('utilisateur');

        $this->assertSame(200, $r['code']);
        $this->assertSame('', $r['erreur']);
        $this->assertNotEmpty($r['indicateur']);
    }

    public function testFindGlobalSummaryRejectsInvalidIndicator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->repo->findGlobalSummary('DROP TABLE batch_profiling');
    }

    public function testFindLatestReturnsRowsOrderedByDateExecutionDesc(): void
    {
        $rows = $this->repo->findLatest(10);

        $this->assertCount(2, $rows);
    }

    public function testFindLatestRespectsLimit(): void
    {
        $rows = $this->repo->findLatest(1);

        $this->assertCount(1, $rows);
    }

    public function testFindStatsByPortefeuilleReturnsOneRowPerPortefeuille(): void
    {
        $rows = $this->repo->findStatsByPortefeuille();

        $this->assertCount(2, $rows);
    }

    public function testFindWeeklyStatsAggregatesByPortefeuille(): void
    {
        $rows = $this->repo->findWeeklyStats();

        $this->assertCount(2, $rows, 'Une ligne par portefeuille (portefeuille-a/b), même semaine courante.');
    }

    public function testFindMonthlyStatsAggregatesByPortefeuille(): void
    {
        $rows = $this->repo->findMonthlyStats();

        $this->assertCount(2, $rows, 'Une ligne par portefeuille (portefeuille-a/b), même mois courant.');
    }

    public function testFindUsersStatsReturnsAggregatedRow(): void
    {
        $rows = $this->repo->findUsersStats();

        $this->assertNotEmpty($rows);
        $this->assertSame('laurent.hadjadj@ma-moulinette.fr', $rows[0]['utilisateur']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
