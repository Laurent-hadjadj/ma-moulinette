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

use App\Entity\NpmScan;
use App\Repository\NpmScanRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\{ConnectionException, UniqueConstraintViolationException};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Couvre handleDatabaseException. findByProjectVersionDate,
 * findLatestForProject et listLatestPerProject (ORM/SQL brut) sont
 * couverts par les tests d'intégration.
 */
#[AllowMockObjectsWithoutExpectations]
class NpmScanRepositoryTest extends TestCase
{
    private NpmScanRepository $repo;

    protected function setUp(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $connection = $this->createMock(Connection::class);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(NpmScan::class));
        $em->method('getConnection')->willReturn($connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        $this->repo = new NpmScanRepository($registry);
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
}
