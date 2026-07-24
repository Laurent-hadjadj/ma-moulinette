<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Historique;
use App\Repository\HistoriqueRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-07-23 : tests Unit pour HistoriqueRepository::selectHistoriqueActuatorInfo
 * (remise à niveau du module Actuator, pastille + modale page Projet).
 */
#[AllowMockObjectsWithoutExpectations]
class HistoriqueRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private HistoriqueRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(Historique::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new HistoriqueRepository($registry);
    }

    public function testSelectHistoriqueActuatorInfoReturnsNullWhenNoRow(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())
            ->method('bindValue')
            ->with(':maven_key', 'fr.ma-moulinette:ma-moulinette');
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn(false);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(200, $response['code']);
        $this->assertNull($response['actuator_info']);
    }

    public function testSelectHistoriqueActuatorInfoDecodesJsonColumn(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn([
            'actuator_info' => '{"date_extraction":"2026-07-23 20:00:26","code":200,"app.version":"1.9.1"}',
        ]);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(200, $response['code']);
        $this->assertSame('1.9.1', $response['actuator_info']['app.version']);
    }

    public function testSelectHistoriqueActuatorInfoReturnsNullWhenColumnIsNull(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn(['actuator_info' => null]);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertNull($response['actuator_info']);
    }

    public function testSelectHistoriqueActuatorInfoReturnsErrorOnDBALException(): void
    {
        $exception = new class('SQL syntax error') extends \RuntimeException implements DBALException {};

        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('SQL syntax error', $response['erreur']);
    }
}
