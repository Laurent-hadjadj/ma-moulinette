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

use App\Repository\RepartitionTempRepository;
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
 * [Description RepartitionTempRepositoryHandlerTest]
 */
class RepartitionTempRepositoryHandlerTest extends TestCase
{
    private static string $gestionTest = 'repartition test';
    private static int $setup = 1000000000001;
    private static string $component = '/src/Controller/ApiController.php';
    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

    public function testSelectRepartitionByTypeAndSeverity_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur insert') extends \Exception implements DBALExceptionInterface {
                public function getSqlState(): null { return null; }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeQuery jette l'exception
        $stmtStub = $this->createStub(Statement::class);
        $stmtStub->method('bindValue');
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection : SELECT pur, pas de transaction
        $connectionMock = $this->createStub(Connection::class);
        $connectionMock->method('prepare')
                                        ->willReturn($stmtStub);

        // 4) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionTempRepository::class)
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
            'category' => 'BUG',
            'severity' => 'CRITICAL',
            'setup' => self::$setup
        ];

        $result = $repo->selectRepartitionByTypeAndSeverity($map);
        $this->assertSame($expected, $result);
    }

    public function testExceptionIsThrown(): void
    {
        // Crée un stub pour Statement qui lance une exception sur executeStatement()
        $stmtStub = $this->createStub(Statement::class);
        $stmtStub->method('executeStatement')
                ->willThrowException(new \Exception('fail'));

        // S’attend à ce que l’exception soit levée ici
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('fail');

        // Appel qui doit lever l’exception
        $stmtStub->executeStatement();
    }

    public function testBatchInsertIssuesSQL_WhenSQLException(): void
    {
        // 1) Crée une vraie exception DBAL
        $fakeException = new class('erreur insert') extends \Exception implements DBALExceptionInterface {
            public function getSqlState(): null { return null; }
        };

        // 2) Mock de Connection : simulate une erreur à l’appel de executeStatement()
        $connectionMock = $this->createStub(Connection::class);
        $connectionMock->method('executeStatement')->willThrowException($fakeException);

        // 3) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 4) Mock partiel du Repository
        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionTempRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        // 5) Simule le retour du handler d'erreur
        $expected = ['code' => 500, 'erreur' => self::$gestionTest];
        $repo->expects($this->once())
            ->method('handleDatabaseException')
            ->with($this->isInstanceOf(\Throwable::class))
            ->willReturn($expected);

        $repo->method('getEntityManager')->willReturn($emStub);

        // 6) Données à insérer
        $issues = [
            [
                'maven_key' => self::$mavenKey,
                'component' => self::$component,
                'category' => 'BUG',
                'severity' => 'CRITICAL',
                'setup' => self::$setup + 1
            ]
        ];

        // 7) Exécution du test
        $result = $repo->batchInsertIssuesSQL($issues);
        $this->assertSame($expected, $result);
    }

    public function testDeleteOldRecords_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur delete') extends \Exception implements DBALExceptionInterface {
                public function getSqlState(): null { return null; }
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
        $repo = $this->getMockBuilder(RepartitionTempRepository::class)
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
                    'setup' => self::$setup + 2,
                ];
        $result = $repo->deleteOldRecords($map);
        $this->assertSame($expected, $result);
    }

}
