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

use App\Entity\UserAgentEvent;
use App\Repository\UserAgentEventRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-06-09 : tests Unit UserAgentEventRepository.
 * Couvre insertUserAgentEvent, selectPendingEvents, updateProcessingStatus
 * et handleDatabaseException (4 cas).
 */
#[AllowMockObjectsWithoutExpectations]
class UserAgentEventRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private UserAgentEventRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(UserAgentEvent::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new UserAgentEventRepository($registry);
    }

    // ──── handleDatabaseException ─────────────────────────────────────────────

    public function testHandleDatabaseExceptionGenericMessage(): void
    {
        $r = $this->repo->handleDatabaseException(new \RuntimeException('boom'));
        $this->assertSame(500, $r['code']);
        $this->assertSame('boom', $r['erreur']);
    }

    public function testHandleDatabaseExceptionConnectionException(): void
    {
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $e = new ConnectionException($driverEx, null);
        $r = $this->repo->handleDatabaseException($e);
        $this->assertSame(500, $r['code']);
        $this->assertSame(UserAgentEventRepository::$noDataBase, $r['erreur']);
    }

    public function testHandleDatabaseExceptionNotNullViolation(): void
    {
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $e = new NotNullConstraintViolationException($driverEx, null);
        $r = $this->repo->handleDatabaseException($e);
        $this->assertSame(500, $r['code']);
    }

    public function testHandleDatabaseExceptionUniqueViolation(): void
    {
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $e = new UniqueConstraintViolationException($driverEx, null);
        $r = $this->repo->handleDatabaseException($e);
        $this->assertSame(23505, $r['code']);
        $this->assertSame('Les informations existent déjà.', $r['erreur']);
    }

    // ──── insertUserAgentEvent ────────────────────────────────────────────────

    private function buildEventMap(): array
    {
        return [
            'event_type'        => 'LOGIN_PAGE_VIEW',
            'url'               => '/login',
            'user_agent'        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'session_id'        => null,
            'user_id'           => null,
            'visitor_id'        => '550e8400-e29b-41d4-a716-446655440000',
            'auth_state'        => 'ANONYMOUS',
            'processing_status' => 'PENDING',
            'ip_hash'           => hash('sha256', '127.0.0.1testsecret'),
            'created_at'        => new \DateTimeImmutable('2026-06-09 10:00:00'),
        ];
    }

    public function testInsertUserAgentEventReturnsSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');
        $this->connection->expects($this->once())->method('commit');

        $result = $this->repo->insertUserAgentEvent($this->buildEventMap());

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
    }

    public function testInsertUserAgentEventWithAuthenticatedUser(): void
    {
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');
        $this->connection->method('commit');

        $map = $this->buildEventMap();
        $map['user_id']    = 7;
        $map['session_id'] = 'session-abc123';
        $map['auth_state'] = 'AUTHENTICATED';
        $map['event_type'] = 'LOGIN_SUCCESS_REDIRECT';

        $result = $this->repo->insertUserAgentEvent($map);

        $this->assertSame(200, $result['code']);
    }

    public function testInsertUserAgentEventRollsBackOnException(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('insert error'));
        $this->connection->expects($this->once())->method('rollBack');
        $this->connection->expects($this->never())->method('commit');

        $result = $this->repo->insertUserAgentEvent($this->buildEventMap());

        $this->assertSame(500, $result['code']);
        $this->assertSame('insert error', $result['erreur']);
    }

    public function testInsertUserAgentEventRollsBackOnConnectionException(): void
    {
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $e = new ConnectionException($driverEx, null);

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willThrowException($e);
        $this->connection->expects($this->once())->method('rollBack');

        $result = $this->repo->insertUserAgentEvent($this->buildEventMap());

        $this->assertSame(500, $result['code']);
        $this->assertSame(UserAgentEventRepository::$noDataBase, $result['erreur']);
    }

    // ──── selectPendingEvents ─────────────────────────────────────────────────

    public function testSelectPendingEventsReturnsRows(): void
    {
        $rows = [
            ['id' => 1, 'event_type' => 'LOGIN_PAGE_VIEW', 'processing_status' => 'PENDING'],
            ['id' => 2, 'event_type' => 'LOGOUT',          'processing_status' => 'PENDING'],
        ];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn($rows);

        $result = $this->repo->selectPendingEvents(10);

        $this->assertSame(200, $result['code']);
        $this->assertSame($rows, $result['liste']);
        $this->assertSame('', $result['erreur']);
    }

    public function testSelectPendingEventsReturnsEmptyListWhenNone(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $result = $this->repo->selectPendingEvents();

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testSelectPendingEventsUsesDefaultLimit(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        // Appel sans paramètre → limit = 20 (défaut)
        $result = $this->repo->selectPendingEvents();

        $this->assertSame(200, $result['code']);
    }

    public function testSelectPendingEventsReturnsErrorOnException(): void
    {
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('select fail'));

        $result = $this->repo->selectPendingEvents();

        $this->assertSame(500, $result['code']);
        $this->assertSame('select fail', $result['erreur']);
    }

    // ──── updateProcessingStatus ──────────────────────────────────────────────

    public function testUpdateProcessingStatusReturnsSuccess(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');

        $result = $this->repo->updateProcessingStatus(1, 'DONE', new \DateTimeImmutable());

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
    }

    public function testUpdateProcessingStatusWithNullProcessedAt(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');

        $result = $this->repo->updateProcessingStatus(1, 'ERROR', null);

        $this->assertSame(200, $result['code']);
    }

    public function testUpdateProcessingStatusWithProcessingState(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');

        $result = $this->repo->updateProcessingStatus(5, 'PROCESSING');

        $this->assertSame(200, $result['code']);
    }

    public function testUpdateProcessingStatusReturnsErrorOnException(): void
    {
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('update fail'));

        $result = $this->repo->updateProcessingStatus(1, 'DONE');

        $this->assertSame(500, $result['code']);
        $this->assertSame('update fail', $result['erreur']);
    }
}
