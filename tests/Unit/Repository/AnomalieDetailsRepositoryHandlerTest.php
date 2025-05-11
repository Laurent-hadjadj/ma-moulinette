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

use App\Repository\AnomalieDetailsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Exception as DBALExceptionInterface;

/**
 * [Description AnomalieDetailsRepositoryHandlerTest]
 */
class AnomalieDetailsRepositoryHandlerTest extends TestCase
{

  public function testSelectAnomalieDetailsMavenKey_WhenSQLException(): void
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
    $connectionMock
        ->method('prepare')
        ->with($this->isType('string'))
        ->willReturn($stmtStub);

    // 4) Stub d'EntityManager pour renvoyer le Connection mocké
    $emStub = $this->createMock(EntityManagerInterface::class);
    $emStub
        ->method('getConnection')
        ->willReturn($connectionMock);

    // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
    $registry = $this->createMock(ManagerRegistry::class);
    $repo = $this->getMockBuilder(AnomalieDetailsRepository::class)
                ->setConstructorArgs([$registry])
                ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
                ->getMock();

    // 6) On s'attend à handleDatabaseException() appelé une fois avec notre exception
    $expected = ['code' => 500, 'erreur' => 'test-error'];
    $repo->expects($this->once())
        ->method('handleDatabaseException')
        ->with($fakeException)
        ->willReturn($expected);

    // 7) getEntityManager() renvoie notre EM stub
    $repo->method('getEntityManager')->willReturn($emStub);

    // 8) Appel de la méthode
    $result = $repo->selectAnomalieDetailsMavenKey(['maven_key' => 'whatever']);

    // 9) Vérification
    $this->assertSame($expected, $result);
  }

  public function testInsertAnomalieDetail_WhenSQLException(): void
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
    $stmtStub->method('executeStatement')
              ->willThrowException($fakeException);

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
    $emStub->method('getConnection')
            ->willReturn($connectionMock);

    // 5) Partial mock du Repository pour surcharger getEntityManager() & handleDatabaseException()
    $registry = $this->createMock(ManagerRegistry::class);
    $repo = $this->getMockBuilder(AnomalieDetailsRepository::class)
                  ->setConstructorArgs([$registry /*, ErrorHandler si présent */])
                  ->onlyMethods(['getEntityManager', 'handleDatabaseException'])
                  ->getMock();

    // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
    $expected = ['code' => 500, 'erreur' => 'gestion test'];
    $repo->expects($this->once())
          ->method('handleDatabaseException')
          ->with($fakeException)
          ->willReturn($expected);

    // 7) getEntityManager() renvoie notre EM stub
    $repo->method('getEntityManager')
          ->willReturn($emStub);

    // 8) Prépare un jeu de données minimal pour le $map (seuls maven_key et name sont bindés ici)
    $dummyDate = new \DateTimeImmutable('2025-05-11 10:00:00');
    $map = [
        'maven_key' => 'MK',
        'name'       => 'Nom',
        'bug_blocker'        => 0,
        'bug_critical'       => 0,
        'bug_major'          => 0,
        'bug_minor'          => 0,
        'bug_info'           => 0,
        'vulnerability_blocker'  => 0,
        'vulnerability_critical' => 0,
        'vulnerability_major'    => 0,
        'vulnerability_minor'    => 0,
        'vulnerability_info'     => 0,
        'code_smell_blocker'     => 0,
        'code_smell_critical'    => 0,
        'code_smell_major'       => 0,
        'code_smell_minor'       => 0,
        'code_smell_info'        => 0,
        'mode_collecte'          => 'auto',
        'utilisateur_collecte'   => 'test',
        'date_enregistrement'    => $dummyDate,
    ];

    // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
    $result = $repo->insertAnomalieDetail($map);

    $this->assertSame($expected, $result);
  }

  public function testDeleteAnomalieDetailsMavenKey_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(AnomalieDetailsRepository::class)
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
    $result = $repo->deleteAnomalieDetailsMavenKey($map);

    $this->assertSame($expected, $result);
  }
}
