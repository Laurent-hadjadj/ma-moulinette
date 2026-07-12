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

namespace App\Tests\Integration\Repository;

use App\Repository\RepartitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALExceptionInterface;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-05-08 : suppression de
 * `withAnyParameters()` et `with($this->isString())` (deprecated PHPUnit 14
 * sans `expects()`), et bascule des mocks "stub-only" (Statement,
 * EntityManager, ManagerRegistry, DBAL Exception) vers `createStub()` pour
 * éteindre les notices "No expectations were configured for the mock object". */

/**
 * [Description RepartitionRepositoryHandlerTest]
 */
class RepartitionRepositoryHandlerTest extends TestCase
{
    private static string $gestionTest = 'gestion test';
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $modeCollecte = 'COLLECTE';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2025-02-17 19:13:59';

    public function testSelectOrUpdateRepartitionInitial_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur insert') extends \Exception implements DBALExceptionInterface {
            public function getSqlState(): null
            {
                return null;
            }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeStatement jette l'exception
        $stmtStub = $this->createStub(Statement::class);
        $stmtStub->method('bindValue');
        $stmtStub->method('executeStatement')->willThrowException($fakeException);
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection : beginTransaction, prepare, rollBack et commit
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())->method('beginTransaction');
        $connectionMock->method('prepare')
            ->willReturn($stmtStub);
        $connectionMock->expects($this->once())->method('rollBack');
        // commit ne doit **jamais** être appelé dans ce scénario
        $connectionMock->expects($this->never())->method('commit');

        // 4) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => self::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($this->isInstanceOf(\Throwable::class))
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $map = [
            'maven_key' => self::$mavenKey,
            'mode_collecte' => self::$modeCollecte,
            'utilisateur_collecte' => self::$utilisateurCollecte,
            'date_enregistrement' => new \DateTimeImmutable(self::$dateEnregistrement)
        ];
        $result = $repo->selectOrUpdateRepartitionInitial($map);
        $this->assertSame($expected, $result);
    }

    public function testUpdateRepartition_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur insert') extends \Exception implements DBALExceptionInterface {
            public function getSqlState(): null
            {
                return null;
            }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeStatement jette l'exception
        $stmtStub = $this->createStub(Statement::class);
        $stmtStub->method('bindValue');
        $stmtStub->method('executeStatement')->willThrowException($fakeException);
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection : beginTransaction, prepare, rollBack et commit
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())->method('beginTransaction');
        $connectionMock->method('prepare')
            ->willReturn($stmtStub);
        $connectionMock->expects($this->once())->method('rollBack');
        // commit ne doit **jamais** être appelé dans ce scénario
        $connectionMock->expects($this->never())->method('commit');

        // 4) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => self::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($this->isInstanceOf(\Throwable::class))
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        // MODIF 2026-05-15 : ajout de la clé 'setup' (obligatoire
        // pour le binding du SQL Check ligne 268 du repo, voir aussi update et insert).
        // Sans cette clé, PHP émet un warning "Undefined array key 'setup'"
        // qui faisait planter le test sous failOnWarning="true".
        $map = [
            'maven_key' => self::$mavenKey,
            'setup' => 'test-setup',
            'mode_collecte' => self::$modeCollecte,
            'utilisateur_collecte' => self::$utilisateurCollecte,
            'date_enregistrement' => new \DateTimeImmutable(self::$dateEnregistrement)
        ];
        $result = $repo->updateRepartition($map);
        $this->assertSame($expected, $result);
    }
}
