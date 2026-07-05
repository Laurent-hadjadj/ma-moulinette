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

use App\Entity\DcFinding;
use App\Repository\DcFindingRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-08 : tests Unit pour DcFindingRepository.
 * Couvre listForScan + handleDatabaseException.
 */
#[AllowMockObjectsWithoutExpectations]
class DcFindingRepositoryTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>>|\Throwable $rowsOrException
     */
    private function buildRepo(array|\Throwable $rowsOrException, ?int $expectedScanParam = null): DcFindingRepository
    {
        $connection = $this->createMock(Connection::class);

        if ($rowsOrException instanceof \Throwable) {
            $matcher = $expectedScanParam !== null
                ? $connection->expects($this->once())->method('executeQuery')->with($this->isString(), ['scan' => $expectedScanParam])
                : $connection->method('executeQuery');
            $matcher->willThrowException($rowsOrException);
        } else {
            $result = $this->createStub(Result::class);
            $result->method('fetchAllAssociative')->willReturn($rowsOrException);

            if ($expectedScanParam !== null) {
                $connection->expects($this->once())
                    ->method('executeQuery')
                    ->with($this->isString(), ['scan' => $expectedScanParam])
                    ->willReturn($result);
            } else {
                $connection->method('executeQuery')->willReturn($result);
            }
        }

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(DcFinding::class));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return new DcFindingRepository($registry);
    }

    public function testHandleDatabaseExceptionFallsBackToGenericMessage(): void
    {
        $repo = $this->buildRepo([]);
        $exception = new \RuntimeException('boom');

        $result = $repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    public function testListForScanReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            [
                'id' => '101',
                'severity_at_scan' => 'CRITICAL',
                'cvss_at_scan' => '9.8',
                'file_name' => 'log4j-core-2.14.0.jar',
                'pkg_coordinates' => 'pkg:maven/org.apache.logging.log4j/log4j-core@2.14.0',
                'vendor' => 'apache',
                'product' => 'log4j-core',
                'dep_version' => '2.14.0',
                'cve_id' => 'CVE-2021-44228',
                'cwes' => '["CWE-502","CWE-917"]',
                'description' => 'Log4Shell',
                'cvss_v3_attack_vector' => 'NETWORK',
            ],
        ], expectedScanParam: 42);

        $result = $repo->listForScan(42);

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(1, $result['liste']);
        $this->assertSame(101, $result['liste'][0]['id']);
        $this->assertSame('CRITICAL', $result['liste'][0]['severity']);
        $this->assertSame(9.8, $result['liste'][0]['cvss']);
        $this->assertSame('log4j-core', $result['liste'][0]['product']);
        $this->assertSame('CVE-2021-44228', $result['liste'][0]['cve_id']);
        $this->assertSame(['CWE-502', 'CWE-917'], $result['liste'][0]['cwes']);
        $this->assertSame('NETWORK', $result['liste'][0]['attack_vector']);
    }

    public function testListForScanHandlesNullCvssAndEmptyCwes(): void
    {
        $repo = $this->buildRepo([
            [
                'id' => '1',
                'severity_at_scan' => 'INFO',
                'cvss_at_scan' => null,
                'file_name' => 'lib.jar',
                'pkg_coordinates' => null,
                'vendor' => null,
                'product' => null,
                'dep_version' => null,
                'cve_id' => 'CVE-2026-00001',
                'cwes' => null,
                'description' => null,
                'cvss_v3_attack_vector' => null,
            ],
        ], expectedScanParam: 1);

        $result = $repo->listForScan(1);

        $this->assertNull($result['liste'][0]['cvss']);
        $this->assertSame([], $result['liste'][0]['cwes']);
    }

    public function testListForScanDecodesCwesJsonProperly(): void
    {
        $repo = $this->buildRepo([
            [
                'id' => '1',
                'severity_at_scan' => 'HIGH',
                'cvss_at_scan' => '7.5',
                'file_name' => 'a.jar',
                'pkg_coordinates' => 'p',
                'vendor' => 'v',
                'product' => 'p',
                'dep_version' => '1',
                'cve_id' => 'CVE-X',
                'cwes' => '["CWE-79"]',
                'description' => 'd',
                'cvss_v3_attack_vector' => 'LOCAL',
            ],
        ], expectedScanParam: 7);

        $result = $repo->listForScan(7);
        $this->assertSame(['CWE-79'], $result['liste'][0]['cwes']);
    }

    public function testListForScanReturnsEmptyListWhenNoData(): void
    {
        $repo = $this->buildRepo([], expectedScanParam: 99);

        $this->assertSame(['code' => 200, 'liste' => [], 'erreur' => ''], $repo->listForScan(99));
    }

    public function testListForScanPassesScanIdParam(): void
    {
        $repo = $this->buildRepo([], expectedScanParam: 12345);

        $result = $repo->listForScan(12345);

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testListForScanReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('db fail'), expectedScanParam: 99);

        $result = $repo->listForScan(99);

        $this->assertSame(500, $result['code']);
        $this->assertSame('db fail', $result['erreur']);
    }
}
