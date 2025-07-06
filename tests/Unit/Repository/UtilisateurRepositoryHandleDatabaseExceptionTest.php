<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Repository;

use App\Repository\UtilisateurRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * [Description UtilisateurRepositoryHandleDatabaseExceptionTest]
 */
class UtilisateurRepositoryHandleDatabaseExceptionTest extends TestCase
{
    private UtilisateurRepository $repo;
    private static $sqlState8006 = 'SQLSTATE[08006] connexion impossible';
    private static $sqlState23505 = 'Les informations existent déjà.';

    protected function setUp(): void
    {
        // On a juste besoin d'un ManagerRegistry bidon pour instancier le repo
        /** @var ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject */
        $registry = $this->createMock(ManagerRegistry::class);
        $this->repo = new UtilisateurRepository($registry);
    }

    public function testHandleDatabaseException_Generic(): void
    {
        // 1) Exception "générique" sans SQLSTATE particulier
        $ex = new class('Une erreur inconnue') extends \Exception {
            public function getSqlState(): ?string { return null; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame(['code' => 500, 'erreur' => 'Une erreur inconnue'], $res);
    }

    public function testHandleDatabaseException_NoDatabase(): void
    {
        // 2) Exception avec message contenant SQLSTATE[08006]
        $ex = new class(static::$sqlState8006) extends \Exception {
            public function getSqlState(): ?string { return null; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame([
            'code'  => 500,
            'erreur'=> static::$sqlState8006
        ], $res);
    }

    public function testHandleDatabaseException_NotNullViolation(): void
    {
        // 3) SQLSTATE 23502 → retour du message d'origine
        $msg = 'Colonne non nulle manquante';
        $ex = new class($msg) extends \Exception {
            private string $msg;
            public function __construct(string $m) { parent::__construct($m); $this->msg = $m; }
            public function getSqlState(): ?string { return '23502'; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame(['code' => 500, 'erreur' => $msg], $res);
    }

    public function testHandleDatabaseException_UniqueViolation(): void
    {
        // 4) SQLSTATE 23505 → code 23505 + message fixe
        $ex = new class(static::$sqlState23505) extends \Exception {
            public function getSqlState(): ?string { return '23505'; }
        };

        $res = $this->repo->handleDatabaseException($ex);

        $this->assertSame([
            'code'  => 500,
            'erreur'=> static::$sqlState23505
        ], $res);
    }

    public function testNoDatabase(): void
    {
        // Créer un mock pour une exception Driver\Exception
      /** @var \Doctrine\DBAL\Driver\Exception|\PHPUnit\Framework\MockObject\MockObject */
        $driverExceptionMock = $this->createMock(\Doctrine\DBAL\Driver\Exception::class);

        // Créer la ConnectionException en passant null pour la requête
        $exception = new \Doctrine\DBAL\Exception\ConnectionException(
            $driverExceptionMock, // Passer le mock Driver\Exception ici
            null, // Passer null pour la requête
            static::$sqlState8006 // Message d'erreur
        );

        // Crée un repository mock
        /** @var \Doctrine\Persistence\ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $repo = new UtilisateurRepository($managerRegistryMock);

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
        /** @var \Doctrine\DBAL\Driver\Exception|\PHPUnit\Framework\MockObject\MockObject */
        $driverExceptionMock = $this->createMock(\Doctrine\DBAL\Driver\Exception::class);

        // Créer la NotNullConstraintViolationException en passant null pour la requête
        $exception = new \Doctrine\DBAL\Exception\NotNullConstraintViolationException(
            $driverExceptionMock, // Passer le mock Driver\Exception ici
            null, // Passer null pour la requête
            'An exception occurred in the driver: ' // Message d'erreur
        );

        // Crée un repository mock
        /** @var \Doctrine\Persistence\ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $repo = new UtilisateurRepository($managerRegistryMock);

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
        /** @var \Doctrine\DBAL\Driver\Exception|\PHPUnit\Framework\MockObject\MockObject */
        $driverExceptionMock = $this->createMock(\Doctrine\DBAL\Driver\Exception::class);

        // Créer la UniqueConstraintViolationException en passant null pour la requête
        $exception = new \Doctrine\DBAL\Exception\UniqueConstraintViolationException(
            $driverExceptionMock, // Passer le mock Driver\Exception ici
            null, // Passer null pour la requête
            static::$sqlState23505 // Message d'erreur
        );

        // Crée un repository mock
        /** @var \Doctrine\Persistence\ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
        $managerRegistryMock = $this->createMock(ManagerRegistry::class);
        $repo = new UtilisateurRepository($managerRegistryMock);

        // Simuler l'appel de la méthode handleDatabaseException
        $result = $repo->handleDatabaseException($exception);

        // Vérifie que le message d'erreur retourné est le bon
        $this->assertSame([
            'code' => 23505,
            'erreur' => static::$sqlState23505,
        ], $result);
    }

}
