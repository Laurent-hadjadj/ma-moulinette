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

namespace App\Tests\Integration\Repository;

use App\Repository\RepartitionRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-05-08 : bascule des mocks "stub-only"
 * (ManagerRegistry, Doctrine\DBAL\Driver\Exception) vers `createStub()` pour
 * éteindre les notices "No expectations were configured for the mock object". */

/**
 * [Description RepartitionRepositoryHandleDatabaseExceptionTest]
 */
class RepartitionRepositoryHandleDatabaseExceptionTest extends TestCase
{
    private RepartitionRepository $repo;
    private static string $sqlState8006 = 'SQLSTATE[08006] connexion impossible';
    private static string $sqlState23505 = 'Les informations existent déjà.';

    protected function setUp(): void
    {
        // On a juste besoin d'un ManagerRegistry bidon pour instancier le repo
        /** @var ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject */
        $registry = $this->createStub(ManagerRegistry::class);
        $this->repo = new RepartitionRepository($registry);
    }

    public function testHandleDatabaseException_Generic(): void
    {
        // 1) Exception "générique" sans SQLSTATE particulier
        $ex = new class('Une erreur inconnue') extends \Exception {
            public function getSqlState(): null { return null; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame(['code' => 500, 'erreur' => 'Une erreur inconnue'], $res);
    }

    public function testHandleDatabaseException_NoDatabase(): void
    {
        // 2) Exception avec message contenant SQLSTATE[08006]
        $ex = new class(self::$sqlState8006) extends \Exception {
            public function getSqlState(): null { return null; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame([
            'code'  => 500,
            'erreur'=> self::$sqlState8006
        ], $res);
    }

    public function testHandleDatabaseException_NotNullViolation(): void
    {
        // 3) SQLSTATE 23502 → retour du message d'origine
        $msg = 'Colonne non nulle manquante';
        $ex = new class($msg) extends \Exception {
            public function __construct(string $m) { parent::__construct($m); }
            public function getSqlState(): string { return '23502'; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame(['code' => 500, 'erreur' => $msg], $res);
    }

    public function testHandleDatabaseException_UniqueViolation(): void
    {
        // 4) SQLSTATE 23505 → code 23505 + message fixe
        $ex = new class(self::$sqlState23505) extends \Exception {
            public function getSqlState(): string { return '23505'; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame([
            'code'  => 500,
            'erreur'=> self::$sqlState23505
        ], $res);
    }

    public function testNoDatabase(): void
    {
        // Créer un mock pour une exception Driver\Exception
      /** @var \Doctrine\DBAL\Driver\Exception&\PHPUnit\Framework\MockObject\MockObject */
        $driverExceptionMock = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);

        // Créer la ConnectionException en passant null pour la requête
        $exception = new \Doctrine\DBAL\Exception\ConnectionException(
            $driverExceptionMock,
            null
        );

        // Crée un repository mock
        /** @var \Doctrine\Persistence\ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject */
        $managerRegistryMock = $this->createStub(ManagerRegistry::class);
        $repo = new RepartitionRepository($managerRegistryMock);

        // Simuler l'appel de la méthode handleDatabaseException
        $result = $repo->handleDatabaseException($exception);

        // Vérifie que le message d'erreur retourné est le bon
        $this->assertSame([
            'code' => 500,
            'erreur' => 'La connexion à la base de données a échoué.',
        ], $result);
    }

    public function testNotNullViolation(): void
    {
        // Créer un mock pour une exception Driver\Exception
        /** @var \Doctrine\DBAL\Driver\Exception&\PHPUnit\Framework\MockObject\MockObject */
        $driverExceptionMock = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);

        // Créer la NotNullConstraintViolationException en passant null pour la requête
        $exception = new \Doctrine\DBAL\Exception\NotNullConstraintViolationException(
            $driverExceptionMock,
            null
        );

        // Crée un repository mock
        /** @var \Doctrine\Persistence\ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject */
        $managerRegistryMock = $this->createStub(ManagerRegistry::class);
        $repo = new RepartitionRepository($managerRegistryMock);

        // Simuler l'appel de la méthode handleDatabaseException
        $result = $repo->handleDatabaseException($exception);

        // Vérifie que le message d'erreur retourné est le bon
        $this->assertSame([
            'code' => 500,
            'erreur' => 'An exception occurred in the driver: ',
        ], $result);
    }

    public function testUniqueViolation(): void
    {
        // Créer un mock pour une exception Driver\Exception
        /** @var \Doctrine\DBAL\Driver\Exception&\PHPUnit\Framework\MockObject\MockObject */
        $driverExceptionMock = $this->createStub(\Doctrine\DBAL\Driver\Exception::class);

        // Créer la UniqueConstraintViolationException en passant null pour la requête
        $exception = new \Doctrine\DBAL\Exception\UniqueConstraintViolationException(
            $driverExceptionMock,
            null
        );

        // Crée un repository mock
        /** @var \Doctrine\Persistence\ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject */
        $managerRegistryMock = $this->createStub(ManagerRegistry::class);
        $repo = new RepartitionRepository($managerRegistryMock);

        // Simuler l'appel de la méthode handleDatabaseException
        $result = $repo->handleDatabaseException($exception);

        // Vérifie que le message d'erreur retourné est le bon
        $this->assertSame([
            'code' => 23505,
            'erreur' => self::$sqlState23505,
        ], $result);
    }

}
