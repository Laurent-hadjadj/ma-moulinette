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

use App\Repository\HotspotOwaspRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception as DBALExceptionInterface;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-05-08: suppression de
 * `withAnyParameters()` et `with($this->isString())` (deprecated PHPUnit 14
 * sans `expects()`), et bascule des mocks "stub-only" (Statement,
 * EntityManager, ManagerRegistry, DBAL Exception) vers `createStub()` pour
 * éteindre les notices "No expectations were configured for the mock object". */

/**
 * [Description HotspotOwaspRepositoryHandlerTest]
 */
class HotspotOwaspRepositoryHandlerTest extends TestCase
{
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $gestionTest = 'gestion test';

    public function testCountHotspotOwaspStatus_WhenSQLException(): void
    {
        // 1) Exception DBAL factice
        $fakeException = $this->createStub(DBALException::class);

        // 2) Mock partiel de Doctrine\DBAL\Statement
        $stmtStub = $this->createStub(Statement::class);

        // bindValue() : pas d'effet
        $stmtStub->method('bindValue');
        // executeQuery() : on jette notre exception
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection dont prepare() renvoie notre Statement mocké
        $connectionMock = $this->createStub(Connection::class);
        $connectionMock->method('prepare')
            ->willReturn($stmtStub);

        // 4) Stub d'EntityManager pour renvoyer le Connection mocké
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')
            ->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(HotspotOwaspRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 6) On s'attend à handleDatabaseException() appelé une fois avec notre exception
        $expected = ['code' => 500, 'erreur' => 'test-error'];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode
        $map = ['maven_key' => self::$mavenKey, 'status' => 'REVIEWED'];
        $result = $repo->countHotspotOwaspStatus($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

    public function testCountHotspotOwaspProbability_WhenSQLException(): void
    {
        // 1) Exception DBAL factice
        $fakeException = $this->createStub(DBALException::class);

        // 2) Mock partiel de Doctrine\DBAL\Statement
        $stmtStub = $this->createStub(Statement::class);

        // bindValue() : pas d'effet
        $stmtStub->method('bindValue');
        // executeQuery() : on jette notre exception
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection dont prepare() renvoie notre Statement mocké
        $connectionMock = $this->createStub(Connection::class);
        $connectionMock->method('prepare')
            ->willReturn($stmtStub);

        // 4) Stub d'EntityManager pour renvoyer le Connection mocké
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')
            ->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(HotspotOwaspRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 6) On s'attend à handleDatabaseException() appelé une fois avec notre exception
        $expected = ['code' => 500, 'erreur' => 'test-error'];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode
        $map = ['maven_key' => self::$mavenKey];
        $result = $repo->countHotspotOwaspProbability($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

    public function testCountHotspotOwaspMenaces_WhenSQLException(): void
    {
        // 1) Exception DBAL factice
        $fakeException = $this->createStub(DBALException::class);

        // 2) Mock partiel de Doctrine\DBAL\Statement
        $stmtStub = $this->createStub(Statement::class);

        // bindValue() : pas d'effet
        $stmtStub->method('bindValue');
        // executeQuery() : on jette notre exception
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection dont prepare() renvoie notre Statement mocké
        $connectionMock = $this->createStub(Connection::class);
        $connectionMock->method('prepare')
            ->willReturn($stmtStub);

        // 4) Stub d'EntityManager pour renvoyer le Connection mocké
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')
            ->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(HotspotOwaspRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 6) On s'attend à handleDatabaseException() appelé une fois avec notre exception
        $expected = ['code' => 500, 'erreur' => 'test-error'];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode
        $map = ['maven_key' => self::$mavenKey];
        $result = $repo->countHotspotOwaspMenaces($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

    public function testCountHotspotOwaspMenaceByStatus_WhenSQLException(): void
    {
        // 1) Exception DBAL factice
        $fakeException = $this->createStub(DBALException::class);

        // 2) Mock partiel de Doctrine\DBAL\Statement
        $stmtStub = $this->createStub(Statement::class);

        // bindValue() : pas d'effet
        $stmtStub->method('bindValue');
        // executeQuery() : on jette notre exception
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection dont prepare() renvoie notre Statement mocké
        $connectionMock = $this->createStub(Connection::class);
        $connectionMock->method('prepare')
            ->willReturn($stmtStub);

        // 4) Stub d'EntityManager pour renvoyer le Connection mocké
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')
            ->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(HotspotOwaspRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 6) On s'attend à handleDatabaseException() appelé une fois avec notre exception
        $expected = ['code' => 500, 'erreur' => 'test-error'];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode
        $map = ['maven_key' => self::$mavenKey, 'menace' => 'a1', 'probability' => 'MEDIUM'];
        $result = $repo->countHotspotOwaspMenaceByStatus($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

    public function testInsertHotspotOwasp_WhenSQLException(): void
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
        $repo = $this->getMockBuilder(HotspotOwaspRepository::class)
            ->setConstructorArgs([$registry])->onlyMethods(['getEntityManager', 'handleDatabaseException'])->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => self::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Prépare un jeu de données minimal pour le $map
        $map = [[
            'referential_owasp' => 2017,
            'version' => '2.0.0-RELEASE',
            'maven_key' => self::$mavenKey,
            'date_version' => new \DateTimeImmutable('2024-08-10 15:26:07+02'),
            'menace' => 'a1',
            'security_category' => 'dos',
            'rule_key' => 'typescript:S5852',
            'probability' => 'MEDIUM',
            'status' => 'TO_REVIEW',
            'resolution' => 'Todo',
            'niveau' => 2,
            'mode_collecte' => 'TRAITEMENT MANUEL',
            'utilisateur_collecte' => 'laurent.hadjadj@ma-moulinette.fr',
            'date_enregistrement' => new \DateTimeImmutable('2024-04-12 16:23:11+01')
        ]];

        // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $result = $repo->insertHotspotOwasp($map);

        $this->assertSame($expected, $result);
    }

    public function testDeleteHotspotOwaspMavenKey_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur delete') extends \Exception implements DBALExceptionInterface {
            public function getSqlState(): null
            {
                return null;
            }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeStatement jette l'exception
        $stmtStub = $this->createStub(Statement::class);
        $stmtStub->method('bindValue');
        $stmtStub->method('executeStatement')->willThrowException($fakeException);

        // 3) Mock de Connection : beginTransaction, prepare, rollBack et commit
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())
            ->method('beginTransaction');
        $connectionMock->method('prepare')
            ->willReturn($stmtStub);
        $connectionMock->expects($this->once())
            ->method('rollBack');
        // commit ne doit **jamais** être appelé dans ce scénario
        $connectionMock->expects($this->never())
            ->method('commit');

        // 4) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(HotspotOwaspRepository::class)
            ->setConstructorArgs([$registry /*, ErrorHandler si présent */])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => self::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Prépare un jeu de données minimal pour le $map
        $map = ['maven_key' => self::$mavenKey];

        // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $result = $repo->deleteHotspotOwaspMavenKey($map);

        $this->assertSame($expected, $result);
    }
}
