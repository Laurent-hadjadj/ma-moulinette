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

use App\Repository\AnomalieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception as DBALExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description AnomalieRepositoryHandlerTest]
 */
class AnomalieRepositoryHandlerTest extends TestCase
{

  public function testDeleteAnomalieMavenKey_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(AnomalieRepository::class)
                  ->setConstructorArgs([$registry /*, ErrorHandler si présent */])
                  ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
                  ->getMock();

    // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
    $expected = ['code' => 500, 'erreur' => 'gestion test'];
    $repo->expects($this->once())->method('handleDatabaseException')
          ->with($fakeException)
          ->willReturn($expected);

    // 7) getEntityManager() renvoie notre EM stub
    $repo->method('getEntityManager')->willReturn($emStub);

    // 8) Prépare un jeu de données minimal pour le $map
    $map = [ 'maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

    // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
    $result = $repo->deleteAnomalieMavenKey($map);

    $this->assertSame($expected, $result);
  }

  public function testSelectAnomalieByProjectName_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(AnomalieRepository::class)
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
    $result = $repo->selectAnomalieByProjectName();

    // 9) Vérification
    $this->assertSame($expected, $result);
  }

  public function testSelectAnomalie_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(AnomalieRepository::class)
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
    $result = $repo->selectAnomalie(['maven_key' => 'some-maven_key']);

    // 9) Vérification
    $this->assertSame($expected, $result);
  }

  public function testInsertAnomalie_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(AnomalieRepository::class)
                  ->setConstructorArgs([$registry])->onlyMethods(['getEntityManager', 'handleDatabaseException'])->getMock();

    // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
    $expected = ['code' => 500, 'erreur' => 'gestion test'];
    $repo->expects($this->once())->method('handleDatabaseException')
          ->with($fakeException)
          ->willReturn($expected);

    // 7) getEntityManager() renvoie notre EM stub
    $repo->method('getEntityManager')->willReturn($emStub);

    // 8) Prépare un jeu de données minimal pour le $map (seuls maven_key et name sont bindés ici)
    $dummyDate = new \DateTimeImmutable('2025-05-11 10:00:00');
    $map = [
      'maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
      'project_name' => 'ma-moulinette',
      'anomalie_total' => 1956,
      'dette_minute' => 19586,
      'dette_reliability_minute' => 107,
      'dette_vulnerability_minute' => 0,
      'dette_code_smell_minute' => 7369,
      'dette_reliability' => '0h:5min',
      'dette_vulnerability' => '0h:0min',
      'dette' => '4d, 19h:32min',
      'dette_code_smell' => '5d, 2h:49min',
      'frontend' => 806,
      'backend' => 0,
      'autre' => 0,
      'blocker' => 0,
      'critical' => 0,
      'major' => 4750,
      'info' => 0,
      'minor' => 222,
      'bug' => 0,
      'vulnerability' => 0,
      'code_smell' => 801,
      'mode_collecte' => 'laurent.hadjadj@ma-petite-entreprise.fr',
      'utilisateur_collecte' => 'COLLECTE',
      'date_enregistrement' => $dummyDate,
    ];
    // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
    $result = $repo->insertAnomalie($map);

    $this->assertSame($expected, $result);
  }

}
