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

namespace App\Tests\Unit\Repository;

use App\Entity\BatchProfiling;
use App\Repository\BatchProfilingRepository;
use Doctrine\DBAL\{Connection, Result};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Couvre les 2 nouvelles méthodes de purge (countOlderThan/purge) —
 * voir src/Command/Prod/BatchProfilingPurgeCommand.php.
 */
#[AllowMockObjectsWithoutExpectations]
class BatchProfilingRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Result&MockObject */                 private MockObject $result;

    private BatchProfilingRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(BatchProfiling::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new BatchProfilingRepository($registry);
    }

    public function testCountOlderThanReturnsIntFromFetchOne(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($this->stringContains('COUNT(*)'), ['days' => 30])
            ->willReturn($this->result);
        $this->result->method('fetchOne')->willReturn('3');

        $this->assertSame(3, $this->repo->countOlderThan(30));
    }

    public function testPurgeCallsPostgresFunctionWithDaysParameter(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($this->stringContains('purge_batch_profiling'), ['days' => 90])
            ->willReturn($this->result);
        $this->result->method('fetchOne')->willReturn('5');

        $this->assertSame(5, $this->repo->purge(90));
    }

    public function testPurgeDefaultsToNinetyDays(): void
    {
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($this->anything(), ['days' => 90])
            ->willReturn($this->result);
        $this->result->method('fetchOne')->willReturn('0');

        $this->repo->purge();
    }
}
