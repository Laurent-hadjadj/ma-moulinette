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

use App\Repository\NoSonarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception as DBALExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description NoSonarRepositoryHandlerTest]
 */
class NoSonarRepositoryHandlerTest extends TestCase
{
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $gestionTest = 'gestion test';

    public function testSelectNoSonarRuleGroupByRule_WhenSQLException(): void
    {
        // 1) Exception DBAL factice
        /** @var DBALException&\Throwable $fakeException */
        $fakeException = $this->createMock(DBALException::class);

        // 2) Mock partiel de Doctrine\DBAL\Statement
        $stmtStub = $this->getMockBuilder(Statement::class)
                        ->disableOriginalConstructor()
                        ->onlyMethods(['bindValue', 'executeQuery'])
                        ->getMock();

        // bindValue() : pas d'effet
        $stmtStub->method('bindValue')->withAnyParameters();
        // executeQuery() : on jette notre exception
        $stmtStub->method('executeQuery')->willThrowException($fakeException);

        // 3) Mock de Connection dont prepare() renvoie notre Statement mocké
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('prepare')
            ->with($this->isType('string'))
            ->willReturn($stmtStub);

        // 4) Stub d'EntityManager pour renvoyer le Connection mocké
        $emStub = $this->createMock(EntityManagerInterface::class);
        $emStub->method('getConnection')
            ->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createMock(ManagerRegistry::class);
        $repo = $this->getMockBuilder(NoSonarRepository::class)
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
        $map = ['maven_key' => static::$mavenKey];
        $result = $repo->selectNoSonarRuleGroupByRule($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

    public function testInsertNoSonar_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur insert') extends \Exception implements DBALExceptionInterface {
            public function getSqlState(): ?string { return null; }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeStatement jette l'exception
        $stmtStub = $this->getMockBuilder(Statement::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['bindValue', 'executeStatement'])
                            ->getMock();
        $stmtStub->method('bindValue')->withAnyParameters();
        $stmtStub->method('executeStatement')->willThrowException($fakeException);

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
        $repo = $this->getMockBuilder(NoSonarRepository::class)
                        ->setConstructorArgs([$registry])->onlyMethods(['getEntityManager', 'handleDatabaseException'])->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => static::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
                ->with($fakeException)
                ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Prépare un jeu de données minimal pour le $map
        $map = [['maven_key' => static::$mavenKey, 'rule' => 'java:S1309',
                'component'=> 'fr.ma-petite-entreprise:mo-moulinette:
                ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java', 'line' => 118,
                'mode_collecte' => 'TRAITEMENT MANUEL','utilisateur_collecte' => 'laurent.hadjadj@ma-petite-entreprise.fr',
                'date_enregistrement'=> new \DateTimeImmutable('2024-03-26 14:46:38')]];

        // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $result = $repo->insertNoSonar($map);

        $this->assertSame($expected, $result);
    }

    public function testDeleteNoSonarMavenKey_WhenSQLException(): void
    {
        // 1) Crée une vraie exception qui implémente DBAL\Exception et Throwable
        $fakeException = new class('erreur delete') extends \Exception implements DBALExceptionInterface {
            public function getSqlState(): ?string { return null; }
        };

        // 2) Stub partiel de Statement : bindValue ok, executeStatement jette l'exception
        $stmtStub = $this->getMockBuilder(Statement::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['bindValue', 'executeStatement'])
                            ->getMock();
        $stmtStub->method('bindValue')->withAnyParameters();
        $stmtStub->method('executeStatement')->willThrowException($fakeException);

        // 3) Mock de Connection : beginTransaction, prepare, rollBack et commit
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())
                        ->method('beginTransaction');
        $connectionMock->method('prepare')
                        ->with($this->isType('string'))
                        ->willReturn($stmtStub);
        $connectionMock->expects($this->once())
                        ->method('rollBack');
        // commit ne doit **jamais** être appelé dans ce scénario
        $connectionMock->expects($this->never())
                        ->method('commit');

        // 4) Stub d'EntityManager pour retourner notre Connection
        $emStub = $this->createMock(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
        $registry = $this->createMock(ManagerRegistry::class);
        $repo = $this->getMockBuilder(NoSonarRepository::class)
                        ->setConstructorArgs([$registry /*, ErrorHandler si présent */])
                        ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
                        ->getMock();

        // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
        $expected = ['code' => 500, 'erreur' => static::$gestionTest];
        $repo->expects($this->once())->method('handleDatabaseException')
                ->with($fakeException)
                ->willReturn($expected);

        // 7) getEntityManager() renvoie notre EM stub
        $repo->method('getEntityManager')->willReturn($emStub);

        // 8) Prépare un jeu de données minimal pour le $map
        $map = [ 'maven_key' => static::$mavenKey ];

        // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
        $result = $repo->deleteNoSonarMavenKey($map);

        $this->assertSame($expected, $result);
    }

}
