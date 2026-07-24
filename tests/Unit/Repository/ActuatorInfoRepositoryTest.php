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

use App\Entity\ActuatorInfo;
use App\Repository\ActuatorInfoRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\{Connection,Result,Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-15 : tests Unit pour ActuatorInfoRepository.
 * Couvre findActuatorInfoById (chemin nominal + exception DBAL).
 */
#[AllowMockObjectsWithoutExpectations]
class ActuatorInfoRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private ActuatorInfoRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(ActuatorInfo::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new ActuatorInfoRepository($registry);
    }

    public function testFindActuatorInfoByIdReturnsRows(): void
    {
        $rows = [
            ['nom' => 'version', 'cle' => 'app.version'],
            ['nom' => 'profile', 'cle' => 'app.profile'],
        ];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())
            ->method('bindValue')
            ->with(':actuator_id', 42);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn($rows);

        $response = $this->repo->findActuatorInfoById(['actuator_id' => 42]);

        $this->assertSame(200, $response['code']);
        $this->assertSame('', $response['erreur']);
        $this->assertSame($rows, $response['liste']);
    }

    public function testFindActuatorInfoByIdReturnsEmptyListWhenNoData(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $response = $this->repo->findActuatorInfoById(['actuator_id' => 1]);

        $this->assertSame(200, $response['code']);
        $this->assertSame([], $response['liste']);
    }

    public function testFindActuatorInfoByIdReturnsErrorOnDBALException(): void
    {
        /* DBALException est une interface ; on construit une exception runtime
           qui l'implémente pour déclencher le catch \Doctrine\DBAL\Exception. */
        $exception = new class('SQL syntax error') extends \RuntimeException implements DBALException {};

        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->findActuatorInfoById(['actuator_id' => 1]);

        $this->assertSame(500, $response['code']);
        $this->assertArrayNotHasKey('liste', $response);
        $this->assertStringContainsString('SQL syntax error', $response['erreur']);
    }
}
