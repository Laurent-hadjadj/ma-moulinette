<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\NpmFinding;
use App\Repository\NpmFindingRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\{ConnectionException, UniqueConstraintViolationException};
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Couvre handleDatabaseException + les 2 branches de markIsProdForScan
 * (liste vide vs liste non vide). listForScan (SQL brut, jointures) est
 * couvert par les tests d'intégration.
 */
#[AllowMockObjectsWithoutExpectations]
class NpmFindingRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;

    private NpmFindingRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->em->method('getClassMetadata')->willReturn(new ClassMetadata(NpmFinding::class));
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new NpmFindingRepository($registry);
    }

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

    public function testHandleDatabaseExceptionUniqueReturnsCustomCode(): void
    {
        $exception = new class('dup') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(23505, $result['code']);
        $this->assertSame('Les informations existent déjà.', $result['erreur']);
    }

    /**
     * MODIF 2026-11-20 : liste vide -> tout passe à is_prod=false directement
     * (pas de `IN ()`, SQL invalide en Postgres).
     */
    public function testMarkIsProdForScanWithEmptyListSetsAllFalse(): void
    {
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->stringContains('SET is_prod = false'),
                ['scan' => 42],
                ['scan' => ParameterType::INTEGER]
            );

        $this->repo->markIsProdForScan(42, []);
    }

    /**
     * MODIF 2026-11-20 : liste non vide -> IN (:names) avec
     * ArrayParameterType::STRING (pas ANY(), qui produirait un SQL invalide
     * avec ce type de paramètre — bug réel corrigé).
     */
    public function testMarkIsProdForScanWithNamesUsesInNotAny(): void
    {
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('d.name IN (:names)'),
                    $this->logicalNot($this->stringContains('ANY('))
                ),
                ['names' => ['vite', 'undici'], 'scan' => 7],
                ['names' => ArrayParameterType::STRING, 'scan' => ParameterType::INTEGER]
            );

        $this->repo->markIsProdForScan(7, ['vite', 'undici']);
    }
}
