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
use App\Repository\UserAgentAnalysisRepository;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\{Connection, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-06-09 : tests Unit UserAgentAnalysisRepository.
 * Couvre insertUserAgentAnalysis et handleDatabaseException (4 cas).
 */
#[AllowMockObjectsWithoutExpectations]
class UserAgentAnalysisRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;

    private UserAgentAnalysisRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);

        $classMetadata = new ClassMetadata(UserAgentAnalysis::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new UserAgentAnalysisRepository($registry);
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
        $this->assertSame(UserAgentAnalysisRepository::$noDataBase, $r['erreur']);
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

    // ──── insertUserAgentAnalysis ─────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function buildAnalysisMap(bool $isBot = false): array
    {
        return [
            'event_type'       => 'ACCUEIL',
            'url'              => '/accueil',
            'session_id'       => 'sess-abc-123',
            'user_id'          => 7,
            'visitor_id'       => '550e8400-e29b-41d4-a716-446655440000',
            'device_type'      => 'desktop',
            'os_name'          => 'Windows',
            'os_version'       => '11',
            'browser_name'     => 'Chrome',
            'browser_version'  => '125.0',
            'is_bot'           => $isBot,
            'detector_version' => '6.5.0',
            'created_at'       => new \DateTimeImmutable('2026-06-09 10:00:00'),
        ];
    }

    public function testInsertUserAgentAnalysisReturnsSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');
        $this->connection->expects($this->once())->method('commit');

        $result = $this->repo->insertUserAgentAnalysis($this->buildAnalysisMap());

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
    }

    public function testInsertUserAgentAnalysisWithBotFlagTrue(): void
    {
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');
        $this->connection->method('commit');

        $result = $this->repo->insertUserAgentAnalysis($this->buildAnalysisMap(true));

        $this->assertSame(200, $result['code']);
    }

    public function testInsertUserAgentAnalysisWithAnonymousUser(): void
    {
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement');
        $this->connection->method('commit');

        $map = $this->buildAnalysisMap();
        $map['user_id']    = null;
        $map['session_id'] = null;
        $map['event_type'] = 'LOGIN_PAGE_VIEW';

        $result = $this->repo->insertUserAgentAnalysis($map);

        $this->assertSame(200, $result['code']);
    }

    public function testInsertUserAgentAnalysisRollsBackOnException(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('analysis insert fail'));
        $this->connection->expects($this->once())->method('rollBack');
        $this->connection->expects($this->never())->method('commit');

        $result = $this->repo->insertUserAgentAnalysis($this->buildAnalysisMap());

        $this->assertSame(500, $result['code']);
        $this->assertSame('analysis insert fail', $result['erreur']);
    }

    public function testInsertUserAgentAnalysisRollsBackOnConnectionException(): void
    {
        $driverEx = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);
        $e = new ConnectionException($driverEx, null);

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willThrowException($e);
        $this->connection->expects($this->once())->method('rollBack');

        $result = $this->repo->insertUserAgentAnalysis($this->buildAnalysisMap());

        $this->assertSame(500, $result['code']);
        $this->assertSame(UserAgentAnalysisRepository::$noDataBase, $result['erreur']);
    }
}
