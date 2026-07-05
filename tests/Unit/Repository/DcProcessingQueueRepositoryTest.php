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

use App\Entity\DcProcessingQueue;
use App\Repository\DcProcessingQueueRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\{ConnectionException, NotNullConstraintViolationException,UniqueConstraintViolationException};
use Doctrine\DBAL\{ParameterType, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-15 : tests Unit pour DcProcessingQueueRepository.
 * Couvre handleDatabaseException (4 branches) + claimNextBatch chemin "queue vide".
 * Les méthodes ORM-only (findByUlid, reclaimStaleProcessing, purgeTerminatedBefore,
 * countByStatus, findByPayloadSha256) sont couvertes par tests d'integration.
 */
#[AllowMockObjectsWithoutExpectations]
class DcProcessingQueueRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private DcProcessingQueueRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(DcProcessingQueue::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new DcProcessingQueueRepository($registry);
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

    public function testHandleDatabaseExceptionNotNullKeepsMessage(): void
    {
        $exception = new class('payload_sha256 not null') extends NotNullConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(500, $result['code']);
        $this->assertSame('payload_sha256 not null', $result['erreur']);
    }

    public function testHandleDatabaseExceptionUniqueReturnsCustomCode(): void
    {
        $exception = new class('dup ulid') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(23505, $result['code']);
        $this->assertSame('Les informations existent déjà.', $result['erreur']);
    }

    /* ============ claimNextBatch ============ */

    public function testClaimNextBatchReturnsEmptyArrayWhenQueueIsEmpty(): void
    {
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FOR UPDATE SKIP LOCKED'))
            ->willReturn($this->statement);

        /* On vérifie que les 2 bindValue sont bien posés avec les bons types. */
        $bindings = [];
        $this->statement->method('bindValue')->willReturnCallback(
            function ($param, $value, $type = ParameterType::STRING) use (&$bindings) {
                $bindings[$param] = ['value' => $value, 'type' => $type];
                return true;
            }
        );
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchFirstColumn')->willReturn([]);

        /* flush ne doit pas être appelé sur queue vide */
        $this->em->expects($this->never())->method('flush');

        $entities = $this->repo->claimNextBatch(5);

        $this->assertSame([], $entities);
        $this->assertSame(DcProcessingQueue::STATUS_QUEUED, $bindings['status']['value']);
        $this->assertSame(5, $bindings['batch_size']['value']);
        $this->assertSame(ParameterType::INTEGER, $bindings['batch_size']['type']);
    }
}
