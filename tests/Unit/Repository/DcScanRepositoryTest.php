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

namespace App\Tests\Unit\Repository;

use App\Entity\DcScan;
use App\Repository\DcScanRepository;
use Doctrine\DBAL\{Connection, Result};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-08 : tests Unit pour DcScanRepository.
 * Couvre listLatestPerProject + handleDatabaseException.
 */
#[AllowMockObjectsWithoutExpectations]
class DcScanRepositoryTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>>|\Throwable $rowsOrException
     */
    private function buildRepo(array|\Throwable $rowsOrException): DcScanRepository
    {
        $connection = $this->createStub(Connection::class);

        if ($rowsOrException instanceof \Throwable) {
            $connection->method('executeQuery')->willThrowException($rowsOrException);
        } else {
            $result = $this->createStub(Result::class);
            $result->method('fetchAllAssociative')->willReturn($rowsOrException);
            $connection->method('executeQuery')->willReturn($result);
        }

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(DcScan::class));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return new DcScanRepository($registry);
    }

    public function testHandleDatabaseExceptionFallsBackToGenericMessage(): void
    {
        $repo = $this->buildRepo([]);
        $exception = new \RuntimeException('boom');

        $result = $repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    public function testListLatestPerProjectReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            [
                'id' => '42',
                'maven_key' => 'fr.example:app-a',
                'project_group' => 'fr.example',
                'project_artifact' => 'app-a',
                'project_version' => '1.0.0',
                'scan_date' => '2026-05-08 12:00:00',
                'dep_count_total' => '120',
                'dep_count_vulnerable' => '15',
                'cve_count_total' => '42',
                'cve_count_critical' => '3',
                'cve_count_high' => '12',
                'cve_count_medium' => '20',
                'cve_count_low' => '7',
            ],
        ]);

        $result = $repo->listLatestPerProject();

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(1, $result['liste']);
        $this->assertSame(42, $result['liste'][0]['id']);
        $this->assertSame('fr.example:app-a', $result['liste'][0]['maven_key']);
        $this->assertSame('1.0.0', $result['liste'][0]['project_version']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['liste'][0]['scan_date']);
        $this->assertSame('2026-05-08 12:00:00', $result['liste'][0]['scan_date']->format('Y-m-d H:i:s'));
        $this->assertSame(120, $result['liste'][0]['dep_count_total']);
        $this->assertSame(3, $result['liste'][0]['cve_count_critical']);
        $this->assertSame(12, $result['liste'][0]['cve_count_high']);
    }

    public function testListLatestPerProjectReturnsEmptyListWhenNoData(): void
    {
        $repo = $this->buildRepo([]);

        $this->assertSame(['code' => 200, 'liste' => [], 'erreur' => ''], $repo->listLatestPerProject());
    }

    public function testListLatestPerProjectCastsCountersToInt(): void
    {
        // PG renvoie tout en string ; on verifie le cast en (int).
        $repo = $this->buildRepo([
            [
                'id' => '1',
                'maven_key' => 'k',
                'project_group' => 'g',
                'project_artifact' => 'a',
                'project_version' => 'v',
                'scan_date' => '2026-01-01 00:00:00',
                'dep_count_total' => '0',
                'dep_count_vulnerable' => '0',
                'cve_count_total' => '0',
                'cve_count_critical' => '0',
                'cve_count_high' => '0',
                'cve_count_medium' => '0',
                'cve_count_low' => '0',
            ],
        ]);

        $result = $repo->listLatestPerProject();
        $this->assertIsInt($result['liste'][0]['id']);
        $this->assertIsInt($result['liste'][0]['dep_count_total']);
        $this->assertIsInt($result['liste'][0]['cve_count_critical']);
    }

    public function testListLatestPerProjectReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('db fail'));

        $result = $repo->listLatestPerProject();

        $this->assertSame(500, $result['code']);
        $this->assertSame('db fail', $result['erreur']);
    }

    /* ============ aggregateSeverityByDay ============
     * MODIF 2026-05-11 : tests Unit. */

    public function testAggregateSeverityByDayReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            ['day' => '2026-05-09', 'critical' => '3', 'high' => '8', 'medium' => '12', 'low' => '5'],
            ['day' => '2026-05-10', 'critical' => '5', 'high' => '10', 'medium' => '15', 'low' => '7'],
        ]);

        $result = $repo->aggregateSeverityByDay();

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(2, $result['liste']);
        $this->assertSame('2026-05-09', $result['liste'][0]['day']);
        $this->assertSame(3, $result['liste'][0]['critical']);
        $this->assertSame(8, $result['liste'][0]['high']);
        $this->assertSame(5, $result['liste'][1]['critical']);
        $this->assertSame(7, $result['liste'][1]['low']);
    }

    public function testAggregateSeverityByDayReturnsEmptyListWhenScopeIsEmptyArray(): void
    {
        $repo = $this->buildRepo([]);

        $result = $repo->aggregateSeverityByDay(30, []);

        $this->assertSame(['code' => 200, 'liste' => [], 'erreur' => ''], $result);
    }

    public function testAggregateSeverityByDayReturnsEmptyListWhenNoData(): void
    {
        $repo = $this->buildRepo([]);

        $result = $repo->aggregateSeverityByDay();

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testAggregateSeverityByDayReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('boom'));

        $result = $repo->aggregateSeverityByDay();

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    /* ============ findArchetypeScansBatch ============
     * MODIF 2026-05-13 : tests early-return.
     * Le path SQL/QueryBuilder demande un test d'integration dédié (vraie BDD)
     * car mocker createQueryBuilder + Query::getResult est fragile. */

    public function testFindArchetypeScansBatchReturnsEmptyMapOnEmptyInput(): void
    {
        $repo = $this->buildRepo([]);

        $this->assertSame([], $repo->findArchetypeScansBatch([]));
    }

    public function testFindArchetypeScansBatchSkipsInvalidPairsWhenAllInvalid(): void
    {
        // Toutes les paires sans label ou sans version filtrées -> tableau utile vide
        // -> pas de SQL execute -> retour [] (vérifie le filtrage défensif).
        $repo = $this->buildRepo([]);

        $result = $repo->findArchetypeScansBatch([
            ['label' => '', 'version' => '1.0.0'],
            ['label' => 'foo', 'version' => ''],
            ['label' => null, 'version' => '1.0.0'],
            ['version' => '1.0.0'], // label manquant
            ['label' => 'foo'],     // version manquante
        ]);

        $this->assertSame([], $result);
    }
}
