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

use App\Entity\Actuator;
use App\Repository\ActuatorRepository;
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
 * MODIF 2026-05-15 : tests Unit pour ActuatorRepository.
 * Couvre handleDatabaseException, deleteActuatorUrl, findActuatorOrderBy,
 * findActuatorOrderByDate, findActuatorMavenKey + sanitize/whitelist.
 */
#[AllowMockObjectsWithoutExpectations]
class ActuatorRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private ActuatorRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(Actuator::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new ActuatorRepository($registry);
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
        $exception = new class('not null') extends NotNullConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(500, $result['code']);
        $this->assertSame('not null', $result['erreur']);
    }

    public function testHandleDatabaseExceptionUniqueReturnsCustomCode(): void
    {
        $exception = new class('dup') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(23505, $result['code']);
        $this->assertSame('Les informations existent déjà.', $result['erreur']);
    }

    /* ============ deleteActuatorUrl ============ */

    public function testDeleteActuatorUrlCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('bindValue')->with(':url', 'http://x/actuator');
        $this->statement->expects($this->once())->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollback');

        $response = $this->repo->deleteActuatorUrl(['url' => 'http://x/actuator']);

        $this->assertSame(200, $response['code']);
        $this->assertSame('', $response['erreur']);
    }

    public function testDeleteActuatorUrlRollsBackOnException(): void
    {
        $exception = new class('cannot delete') extends \RuntimeException implements DBALException {};
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollback');

        $response = $this->repo->deleteActuatorUrl(['url' => 'http://x']);

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('cannot delete', $response['erreur']);
    }

    /* ============ findActuatorOrderBy ============ */

    public function testFindActuatorOrderByReturnsPaginatorQueryRows(): void
    {
        $rows = [['id' => 1, 'nom_application' => 'App1']];
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ORDER BY url ASC'))
            ->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn($rows);

        $response = $this->repo->findActuatorOrderBy('url', 'ASC');

        $this->assertSame(200, $response['code']);
        $this->assertSame($rows, $response['paginator_query']);
    }

    public function testFindActuatorOrderByFallsBackToNomApplicationOnInvalidColumn(): void
    {
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ORDER BY nom_application'))
            ->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $response = $this->repo->findActuatorOrderBy("id; DROP TABLE actuator;--", 'ASC');

        $this->assertSame(200, $response['code']);
        $this->assertSame([], $response['paginator_query']);
    }

    public function testFindActuatorOrderByFallsBackToDescOnInvalidDirection(): void
    {
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ORDER BY url DESC'))
            ->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $response = $this->repo->findActuatorOrderBy('url', 'pwn');

        $this->assertSame(200, $response['code']);
    }

    public function testFindActuatorOrderByReturnsErrorOnException(): void
    {
        $exception = new class('SQL boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->findActuatorOrderBy('url', 'ASC');

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('SQL boom', $response['erreur']);
    }

    /* ============ findActuatorOrderByDate ============ */

    public function testFindActuatorOrderByDateNominal(): void
    {
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ASC'))
            ->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([['id' => 1]]);

        $response = $this->repo->findActuatorOrderByDate('ASC');

        $this->assertSame(200, $response['code']);
        $this->assertCount(1, $response['paginator_query']);
    }

    public function testFindActuatorOrderByDateFallsBackToDescOnInvalidDirection(): void
    {
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DESC'))
            ->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $response = $this->repo->findActuatorOrderByDate('not-a-direction');

        $this->assertSame(200, $response['code']);
    }

    public function testFindActuatorOrderByDateReturnsErrorOnException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->findActuatorOrderByDate('ASC');

        $this->assertSame(500, $response['code']);
    }

    /* ============ findActuatorMavenKey ============ */

    public function testFindActuatorMavenKeyReturnsRow(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('bindValue')->with(':maven_key', 'fr.test:app');
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([[
            'id' => 7,
            'url' => 'http://host/actuator',
            'actuator_user' => 'admin',
            'actuator_password' => 'secret',
        ]]);

        $response = $this->repo->findActuatorMavenKey(['maven_key' => 'fr.test:app']);

        $this->assertSame(200, $response['code']);
        $this->assertSame(7, $response['id']);
        $this->assertSame('http://host/actuator', $response['url']);
        $this->assertSame('admin', $response['user']);
        $this->assertSame('secret', $response['password']);
    }

    public function testFindActuatorMavenKeyReturns404WhenEmpty(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $response = $this->repo->findActuatorMavenKey(['maven_key' => 'unknown']);

        $this->assertSame(404, $response['code']);
        $this->assertStringContainsString("point d'accès", $response['message']);
    }

    public function testFindActuatorMavenKeyTreatsMissingUserPasswordAsNull(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([[
            'id' => 1,
            'url' => 'http://host',
            'actuator_user' => null,
            'actuator_password' => null,
        ]]);

        $response = $this->repo->findActuatorMavenKey(['maven_key' => 'k']);

        $this->assertSame(200, $response['code']);
        $this->assertNull($response['user']);
        $this->assertNull($response['password']);
    }

    public function testFindActuatorMavenKeyReturnsErrorOnException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->findActuatorMavenKey(['maven_key' => 'k']);

        $this->assertSame(500, $response['code']);
    }
}
