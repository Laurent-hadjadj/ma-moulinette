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

use App\Entity\UserRoleLog;
use App\Repository\UserRoleLogRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\{Connection, Result};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UserRoleLogRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Result&MockObject */                 private MockObject $result;

    private UserRoleLogRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->result      = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(UserRoleLog::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new UserRoleLogRepository($registry);
    }

    /* ============ handleDatabaseException ============ */

    public function testHandleDatabaseExceptionGenericMessage(): void
    {
        $result = $this->repo->handleDatabaseException(new \RuntimeException('boom'));
        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    public function testHandleDatabaseExceptionConnection(): void
    {
        $exception = new class('SQLSTATE[08006]') extends ConnectionException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(500, $result['code']);
        $this->assertStringContainsString('connexion', $result['erreur']);
    }

    /* ============ findFiltered ============ */

    public function testFindFilteredReturnsListOnSuccess(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([['id' => 1]]);
        $this->connection->method('executeQuery')->willReturn($this->result);

        $result = $this->repo->findFiltered([]);

        $this->assertSame(200, $result['code']);
        $this->assertCount(1, $result['liste']);
    }

    public function testFindFilteredAddsCourrielClauseWhenProvided(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([]);
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains('ILIKE'),
                $this->callback(fn($params) => ($params['courriel'] ?? null) === '%emma%'),
                $this->anything(),
            )
            ->willReturn($this->result);

        $this->repo->findFiltered(['courriel' => 'emma']);
    }

    public function testFindFilteredReturns500OnException(): void
    {
        $this->connection->method('executeQuery')->willThrowException(new \RuntimeException('boom'));

        $result = $this->repo->findFiltered([]);

        $this->assertSame(500, $result['code']);
    }

    /* ============ findByIds ============ */

    public function testFindByIdsReturnsEmptyListWithoutQueryingWhenNoIds(): void
    {
        $this->connection->expects($this->never())->method('executeQuery');

        $result = $this->repo->findByIds([]);

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testFindByIdsReturnsListOnSuccess(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([['id' => 1], ['id' => 2]]);
        $this->connection->method('executeQuery')->willReturn($this->result);

        $result = $this->repo->findByIds([1, 2]);

        $this->assertSame(200, $result['code']);
        $this->assertCount(2, $result['liste']);
    }

    /* ============ deleteByIds ============ */

    public function testDeleteByIdsReturnsZeroWithoutTransactionWhenNoIds(): void
    {
        $this->connection->expects($this->never())->method('beginTransaction');

        $result = $this->repo->deleteByIds([]);

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['supprime']);
    }

    public function testDeleteByIdsCommitsAndReturnsCount(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('executeStatement')->willReturn(3);
        $this->connection->expects($this->once())->method('commit');

        $result = $this->repo->deleteByIds([1, 2, 3]);

        $this->assertSame(200, $result['code']);
        $this->assertSame(3, $result['supprime']);
    }

    public function testDeleteByIdsRollsBackOnException(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('executeStatement')->willThrowException(new \RuntimeException('boom'));
        $this->connection->expects($this->once())->method('rollback');

        $result = $this->repo->deleteByIds([1]);

        $this->assertSame(500, $result['code']);
    }
}
