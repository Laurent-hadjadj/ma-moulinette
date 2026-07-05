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

use App\Entity\DcCve;
use App\Repository\DcCveRepository;
use Doctrine\DBAL\{Connection, Result};
use Doctrine\DBAL\Exception\{ConnectionException, UniqueConstraintViolationException};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-08 : tests Unit pour DcCveRepository.
 * Couvre handleDatabaseException, countProjectsByCwe et findCriticalCvesAcrossProjects.
 * Pattern repository projet : retour ['code' => 200, 'liste' => [...], 'erreur' => '']
 * ou ['code' => 500|23505, 'erreur' => '...'].
 */
#[AllowMockObjectsWithoutExpectations]
class DcCveRepositoryTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>>|\Throwable $rowsOrException
     */
    private function buildRepo(array|\Throwable $rowsOrException, ?int $expectedMinParam = null): DcCveRepository
    {
        $connection = $this->createMock(Connection::class);

        if ($rowsOrException instanceof \Throwable) {
            $matcher = $expectedMinParam !== null
                ? $connection->expects($this->once())->method('executeQuery')->with($this->isString(), ['min' => $expectedMinParam])
                : $connection->method('executeQuery');
            $matcher->willThrowException($rowsOrException);
        } else {
            $result = $this->createStub(Result::class);
            $result->method('fetchAllAssociative')->willReturn($rowsOrException);

            if ($expectedMinParam !== null) {
                $connection->expects($this->once())
                    ->method('executeQuery')
                    ->with($this->isString(), ['min' => $expectedMinParam])
                    ->willReturn($result);
            } else {
                $connection->method('executeQuery')->willReturn($result);
            }
        }

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(DcCve::class));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return new DcCveRepository($registry);
    }

    /* ============ handleDatabaseException ============ */

    public function testHandleDatabaseExceptionReturnsConnectionMessage(): void
    {
        $repo = $this->buildRepo([]);
        $exception = new class('SQLSTATE[08006]') extends ConnectionException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertStringContainsString('connexion', $result['erreur']);
    }

    public function testHandleDatabaseExceptionReturnsUniqueConstraintMessage(): void
    {
        $repo = $this->buildRepo([]);
        $exception = new class('unique') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $repo->handleDatabaseException($exception);

        $this->assertSame(23505, $result['code']);
        $this->assertStringContainsString('existent déjà', $result['erreur']);
    }

    public function testHandleDatabaseExceptionFallsBackToGenericMessage(): void
    {
        $repo = $this->buildRepo([]);
        $exception = new \RuntimeException('boom');

        $result = $repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    /* ============ countProjectsByCwe ============ */

    public function testCountProjectsByCweReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            ['cwe' => 'CWE-79',  'nb_projets' => '4', 'projets' => '{"app-a","app-b","app-c","app-d"}'],
            ['cwe' => 'CWE-502', 'nb_projets' => '2', 'projets' => '{"app-a","app-b"}'],
        ]);

        $result = $repo->countProjectsByCwe();

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(2, $result['liste']);
        $this->assertSame('CWE-79', $result['liste'][0]['cwe']);
        $this->assertSame(4, $result['liste'][0]['nb_projets']);
        $this->assertSame(['app-a', 'app-b', 'app-c', 'app-d'], $result['liste'][0]['projets']);
        $this->assertSame(2, $result['liste'][1]['nb_projets']);
    }

    public function testCountProjectsByCweReturnsEmptyListWhenNoData(): void
    {
        $repo = $this->buildRepo([]);

        $this->assertSame(['code' => 200, 'liste' => [], 'erreur' => ''], $repo->countProjectsByCwe());
    }

    public function testCountProjectsByCweHandlesEmptyPgArray(): void
    {
        $repo = $this->buildRepo([
            ['cwe' => 'CWE-22', 'nb_projets' => '0', 'projets' => '{}'],
        ]);

        $result = $repo->countProjectsByCwe();
        $this->assertSame([], $result['liste'][0]['projets']);
    }

    public function testCountProjectsByCweReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('db fail'));

        $result = $repo->countProjectsByCwe();

        $this->assertSame(500, $result['code']);
        $this->assertSame('db fail', $result['erreur']);
        $this->assertArrayNotHasKey('liste', $result);
    }

    /* ============ findCriticalCvesAcrossProjects ============ */

    public function testFindCriticalCvesAcrossProjectsReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            [
                'cve_id' => 'CVE-2024-12345',
                'severity' => 'CRITICAL',
                'cvss_v3_score' => '9.8',
                'description' => 'Remote code execution',
                'nb_projets' => '3',
                'projets' => '{"app-a","app-b","app-c"}',
            ],
            [
                'cve_id' => 'CVE-2024-99999',
                'severity' => 'HIGH',
                'cvss_v3_score' => null,
                'description' => 'XSS',
                'nb_projets' => '2',
                'projets' => '{"app-x","app-y"}',
            ],
        ], expectedMinParam: 2);

        $result = $repo->findCriticalCvesAcrossProjects(2);

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(2, $result['liste']);
        $this->assertSame('CVE-2024-12345', $result['liste'][0]['cve_id']);
        $this->assertSame('CRITICAL', $result['liste'][0]['severity']);
        $this->assertSame(9.8, $result['liste'][0]['cvss_v3_score']);
        $this->assertSame(3, $result['liste'][0]['nb_projets']);
        $this->assertSame(['app-a', 'app-b', 'app-c'], $result['liste'][0]['projets']);
        $this->assertNull($result['liste'][1]['cvss_v3_score']);
    }

    public function testFindCriticalCvesAcrossProjectsPassesMinProjectsParam(): void
    {
        $repo = $this->buildRepo([], expectedMinParam: 5);

        $result = $repo->findCriticalCvesAcrossProjects(5);

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testFindCriticalCvesAcrossProjectsReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('boom'), expectedMinParam: 2);

        $result = $repo->findCriticalCvesAcrossProjects(2);

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    /* ============ listTopCriticalCves ============
     * MODIF 2026-05-11 : tests Unit pour
     * la nouvelle méthode. Pattern aligne sur findCriticalCvesAcrossProjects. */

    public function testListTopCriticalCvesReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            [
                'cve_id' => 'CVE-2024-11111',
                'severity' => 'CRITICAL',
                'cvss_v3_score' => '9.9',
                'description' => 'log4j RCE',
                'nb_projets' => '7',
                'projets' => '{"app-a","app-b","app-c"}',
            ],
            [
                'cve_id' => 'CVE-2024-22222',
                'severity' => 'CRITICAL',
                'cvss_v3_score' => null,
                'description' => 'jackson-databind unsafe deser',
                'nb_projets' => '3',
                'projets' => '{"app-x"}',
            ],
        ]);

        $result = $repo->listTopCriticalCves(10);

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(2, $result['liste']);
        $this->assertSame('CVE-2024-11111', $result['liste'][0]['cve_id']);
        $this->assertSame('CRITICAL', $result['liste'][0]['severity']);
        $this->assertSame(9.9, $result['liste'][0]['cvss_v3_score']);
        $this->assertSame(7, $result['liste'][0]['nb_projets']);
        $this->assertSame(['app-a', 'app-b', 'app-c'], $result['liste'][0]['projets']);
        $this->assertNull($result['liste'][1]['cvss_v3_score']);
        $this->assertSame(['app-x'], $result['liste'][1]['projets']);
    }

    public function testListTopCriticalCvesReturnsEmptyListWhenScopeIsEmptyArray(): void
    {
        // Scope = [] : retour direct sans toucher la DB. On ne stub
        // intentionnellement aucune Connection ici pour valider le shortcut.
        $repo = $this->buildRepo([]);

        $result = $repo->listTopCriticalCves(10, []);

        $this->assertSame(['code' => 200, 'liste' => [], 'erreur' => ''], $result);
    }

    public function testListTopCriticalCvesReturnsEmptyListWhenNoData(): void
    {
        $repo = $this->buildRepo([]);

        $result = $repo->listTopCriticalCves(10);

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testListTopCriticalCvesReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('boom'));

        $result = $repo->listTopCriticalCves(10);

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    /* ============ listNewCriticalSinceLastScan ============
     * MODIF 2026-05-12 : tests Unit.
     * Vraie diff CVE CRITICAL latest scan vs scan précédent. Le mapping
     * inclut le flag is_first_scan (true si pas de scan antérieur). */

    public function testListNewCriticalSinceLastScanReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            [
                'cve_id' => 'CVE-2024-77777',
                'cvss_v3_score' => '9.8',
                'project_group' => 'fr-test',
                'project_artifact' => 'app-a',
                'project_version' => '1.0.0',
                'maven_key' => 'fr-test:app-a',
                'scan_date' => '2026-05-10 14:30:00',
                'dep_product' => 'log4j-core',
                'dep_version' => '2.14.0',
                'is_first_scan' => false,
            ],
            [
                'cve_id' => 'CVE-2024-88888',
                'cvss_v3_score' => null,
                'project_group' => 'fr-test',
                'project_artifact' => 'app-b',
                'project_version' => '2.0.0',
                'maven_key' => 'fr-test:app-b',
                'scan_date' => '2026-05-08 09:00:00',
                'dep_product' => 'jackson-databind',
                'dep_version' => '2.9.10',
                'is_first_scan' => true,
            ],
        ]);

        $result = $repo->listNewCriticalSinceLastScan();

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(2, $result['liste']);

        $first = $result['liste'][0];
        $this->assertSame('CVE-2024-77777', $first['cve_id']);
        $this->assertSame(9.8, $first['cvss_v3_score']);
        $this->assertSame('app-a', $first['project_artifact']);
        $this->assertSame('log4j-core', $first['dep_product']);
        $this->assertFalse($first['is_first_scan']);
        $this->assertArrayNotHasKey('fixed_version', $first);

        $second = $result['liste'][1];
        $this->assertNull($second['cvss_v3_score']);
        $this->assertTrue($second['is_first_scan']);
    }

    public function testListNewCriticalSinceLastScanReturnsEmptyListWhenScopeIsEmptyArray(): void
    {
        $repo = $this->buildRepo([]);

        $result = $repo->listNewCriticalSinceLastScan(50, []);

        $this->assertSame(['code' => 200, 'liste' => [], 'erreur' => ''], $result);
    }

    public function testListNewCriticalSinceLastScanReturnsEmptyListWhenNoData(): void
    {
        $repo = $this->buildRepo([]);

        $result = $repo->listNewCriticalSinceLastScan();

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testListNewCriticalSinceLastScanReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('boom'));

        $result = $repo->listNewCriticalSinceLastScan();

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }
}
