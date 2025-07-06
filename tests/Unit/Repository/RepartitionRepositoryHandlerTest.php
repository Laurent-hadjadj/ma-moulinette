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

use App\Repository\RepartitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description RepartitionRepositoryHandlerTest]
 */
class RepartitionRepositoryHandlerTest extends TestCase
{
    private static $gestionTest = 'gestion test';
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $modeCollecte = 'COLLECTE';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2025-02-17 19:13:59';

    public function testSelectOrUpdateRepartitionInitial_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur insert') extends \Exception implements DBALExceptionInterface {
                public function getSqlState(): ?string { return null; }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeStatement jette l'exception
        $stmtStub = $this->getMockBuilder(Statement::class)
                                        ->disableOriginalConstructor()
                                        ->onlyMethods(['bindValue', 'executeStatement', 'executeQuery'])
                                        ->getMock();
        $stmtStub->method('bindValue')->withAnyParameters();
        $stmtStub->method('executeStatement')->willThrowException($fakeException);
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection : beginTransaction, prepare, rollBack et commit
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())->method('beginTransaction');
        $connectionMock->method('prepare')->with($this->isType('string'))
                                        ->willReturn($stmtStub);
        $connectionMock->expects($this->once())->method('rollBack');
        // commit ne doit **jamais** être appelé dans ce scénario
        $connectionMock->expects($this->never())->method('commit');

        // 4) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createMock(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createMock(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionRepository::class)
                                ->setConstructorArgs([$registry])
                                ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
                                ->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => static::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
                ->with($this->isInstanceOf(\Throwable::class))
                ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $map = [
                'maven_key' => static::$mavenKey,
                'mode_collecte' => static::$modeCollecte,
                'utilisateur_collecte' => static::$utilisateurCollecte,
                'date_enregistrement' => new \DateTimeImmutable(static::$dateEnregistrement)
        ];
        $result = $repo->selectOrUpdateRepartitionInitial($map);
        $this->assertSame($expected, $result);
    }

    public function testUpdateRepartition_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur insert') extends \Exception implements DBALExceptionInterface {
                public function getSqlState(): ?string { return null; }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeStatement jette l'exception
        $stmtStub = $this->getMockBuilder(Statement::class)
                                        ->disableOriginalConstructor()
                                        ->onlyMethods(['bindValue', 'executeStatement', 'executeQuery'])
                                        ->getMock();
        $stmtStub->method('bindValue')->withAnyParameters();
        $stmtStub->method('executeStatement')->willThrowException($fakeException);
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection : beginTransaction, prepare, rollBack et commit
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())->method('beginTransaction');
        $connectionMock->method('prepare')->with($this->isType('string'))
                                        ->willReturn($stmtStub);
        $connectionMock->expects($this->once())->method('rollBack');
        // commit ne doit **jamais** être appelé dans ce scénario
        $connectionMock->expects($this->never())->method('commit');

        // 4) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createMock(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createMock(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionRepository::class)
                                ->setConstructorArgs([$registry])
                                ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
                                ->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => static::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
                ->with($this->isInstanceOf(\Throwable::class))
                ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $map = [
                'maven_key' => static::$mavenKey,
                'mode_collecte' => static::$modeCollecte,
                'utilisateur_collecte' => static::$utilisateurCollecte,
                'date_enregistrement' => new \DateTimeImmutable(static::$dateEnregistrement)
        ];
        $result = $repo->updateRepartition($map);
        $this->assertSame($expected, $result);
    }

}
