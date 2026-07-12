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

use App\Repository\OwaspRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception as DBALExceptionInterface;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-05-08 : suppression de
 * `withAnyParameters()` et `with($this->isString())` (deprecated PHPUnit 14
 * sans `expects()`), et bascule des mocks "stub-only" (Statement,
 * EntityManager, ManagerRegistry, DBAL Exception) vers `createStub()` pour
 * éteindre les notices "No expectations were configured for the mock object". */

/**
 * [Description OwaspRepositoryHandlerTest]
 */
class OwaspRepositoryHandlerTest extends TestCase
{
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $gestionTest = 'gestion test';
    private static string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static int $effortTotal = 0;
    private static $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

    /* MODIF 2026-05-10 : test exception SQL pour la nouvelle
     * méthode selectOwaspVersion (utilisée dans le breadcrumb OWASP). */
    public function testSelectOwaspVersion_WhenSQLException(): void
    {
        $fakeException = $this->createStub(DBALException::class);

        $stmtStub = $this->createStub(Statement::class);
        $stmtStub->method('bindValue');
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        $connectionMock = $this->createStub(Connection::class);
        $connectionMock->method('prepare')->willReturn($stmtStub);

        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(OwaspRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
            ->getMock();

        $expected = ['code' => 500, 'erreur' => 'test-error'];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        $repo->method('getEntityManager')->willReturn($emStub);

        $result = $repo->selectOwaspVersion(self::$mavenKey);

        $this->assertSame($expected, $result);
    }

    public function testSelectOwaspOrderByDateEnregistrement_WhenSQLException(): void
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
        $repo = $this->getMockBuilder(OwaspRepository::class)
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
        $map = ['maven_key' => self::$mavenKey, 'referential_owasp' => 2017];
        $result = $repo->selectOwaspOrderByDateEnregistrement($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

    public function testInsertOwasp_WhenSQLException(): void
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
        $repo = $this->getMockBuilder(OwaspRepository::class)
            ->setConstructorArgs([$registry])->onlyMethods(['getEntityManager', 'handleDatabaseException'])->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => self::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Prépare un jeu de données minimal pour le $map
        $map = [];

        // Ajouter les propriétés de l'objet $owasp au tableau $map
        $map['referential_owasp'] = 2017;
        $map['maven_key'] = self::$mavenKey;
        $map['version'] = self::$version;
        $map['date_version'] = self::$dateVersion;
        $map['effort_total'] = self::$effortTotal;

        for ($i = 0; $i < 10; $i++) {
            $map["a" . ($i + 1)] = self::$a[$i];
            $map["a" . ($i + 1) . "_blocker"] = self::$aBlocker[$i];
            $map["a" . ($i + 1) . "_critical"] = self::$aCritical[$i];
            $map["a" . ($i + 1) . "_major"] = self::$aMajor[$i];
            $map["a" . ($i + 1) . "_info"] = self::$aInfo[$i];
            $map["a" . ($i + 1) . "_minor"] = self::$aMinor[$i];
        }

        $map['mode_collecte'] = 'COLLECTE';
        $map['utilisateur_collecte'] = 'laurent.hadjadj@ma-moulinette.fr';
        $map['date_enregistrement'] = new \DateTimeImmutable('2024-03-26 14:46:38');

        // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $result = $repo->insertOwasp([$map]);

        $this->assertSame($expected, $result);
    }

    public function testDeleteOwaspMavenKey_WhenSQLException(): void
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
        $repo = $this->getMockBuilder(OwaspRepository::class)
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
        $result = $repo->deleteOwaspMavenKey($map);

        $this->assertSame($expected, $result);
    }
}
