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

use App\Entity\Batch;
use App\Repository\BatchRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\{ConnectionException, NotNullConstraintViolationException,UniqueConstraintViolationException};
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-15 : tests Unit pour BatchRepository.
 * Couvre handleDatabaseException (4 branches) + selectBatchByStatut + updatePortefeuille.
 */
#[AllowMockObjectsWithoutExpectations]
class BatchRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private BatchRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(Batch::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new BatchRepository($registry);
    }

    /* ============ handleDatabaseException ============ */

    public function testHandleDatabaseExceptionGenericMessage(): void
    {
        $result = $this->repo->handleDatabaseException(new \RuntimeException('boom'));

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    public function testHandleDatabaseExceptionConnectionUsesNoDataBaseMessage(): void
    {
        $exception = new class('SQLSTATE[08006]') extends ConnectionException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertStringContainsString('connexion', $result['erreur']);
    }

    public function testHandleDatabaseExceptionNotNullKeepsOriginalMessage(): void
    {
        $exception = new class('column responsable cannot be null') extends NotNullConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('column responsable cannot be null', $result['erreur']);
    }

    public function testHandleDatabaseExceptionUniqueReturnsCustomCode(): void
    {
        $exception = new class('duplicate') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(23505, $result['code']);
        $this->assertSame('Les informations existent déjà.', $result['erreur']);
    }

    /* ============ selectBatchByStatut ============ */

    public function testSelectBatchByStatutReturnsRows(): void
    {
        $rows = [
            ['activated' => true, 'titre' => 'Daily', 'responsable' => 'admin', 'portefeuille' => 'P1', 'nombre' => 5],
            ['activated' => false, 'titre' => 'Weekly', 'responsable' => 'admin', 'portefeuille' => 'P2', 'nombre' => 3],
        ];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn($rows);

        $response = $this->repo->selectBatchByStatut();

        $this->assertSame(200, $response['code']);
        $this->assertSame('', $response['erreur']);
        $this->assertSame($rows, $response['liste']);
    }

    public function testSelectBatchByStatutReturnsErrorOnException(): void
    {
        $exception = new class('SQL syntax error') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->selectBatchByStatut();

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('SQL syntax error', $response['erreur']);
    }

    /* ============ updatePortefeuille ============ */

    public function testUpdatePortefeuilleCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->exactly(2))->method('bindValue');
        $this->statement->expects($this->once())->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');

        $response = $this->repo->updatePortefeuille([
            'portefeuille'  => 'PF-A',
            'nombre_projet' => 7,
        ]);

        $this->assertSame(200, $response['code']);
        $this->assertSame('', $response['erreur']);
    }

    public function testUpdatePortefeuilleRollsBackOnException(): void
    {
        $exception = new class('cannot update') extends \RuntimeException implements DBALException {};

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollBack');

        $response = $this->repo->updatePortefeuille([
            'portefeuille'  => 'PF-A',
            'nombre_projet' => 7,
        ]);

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('cannot update', $response['erreur']);
    }
}
