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

use App\Repository\HistoriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception as DBALExceptionInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description HistoriqueRepositoryHandlerTest]
 */
class HistoriqueRepositoryHandlerTest extends TestCase
{
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $gestionTest = 'gestion test';

    public function testCountHistoriqueProjet_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $result = $repo->countHistoriqueProjet($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testGetProjetFavori_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $where = "'maven_key' = ".static::$mavenKey;
    $result = $repo->getProjetFavori($where);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testUpdateHistoriqueReference_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
                    ->setConstructorArgs([$registry])->onlyMethods(['getEntityManager', 'handleDatabaseException'])->getMock();

    // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
    $expected = ['code' => 500, 'erreur' => static::$gestionTest];
    $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

    // 7) getEntityManager() renvoie notre EM stub
    $repo->method('getEntityManager')->willReturn($emStub);

    // 8) Prépare un jeu de données minimal pour le $map
    $map = [ 'maven_key' => static::$mavenKey];

    // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
    $result = $repo->updateHistoriqueReference($map);

    $this->assertSame($expected, $result);
    }

    public function testDeleteHistoriqueProjet_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = [ 'maven_key' => static::$mavenKey, 'version' => '1.2.0-RELEASE', 'date_version' => '2024-07-12 16:34:46'];

    // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
    $result = $repo->deleteHistoriqueProjet($map);

    $this->assertSame($expected, $result);
    }

    public function testSelectUnionHistoriqueProjet_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = ['maven_key' => static::$mavenKey, 'limit' => 5];
    $result = $repo->selectUnionHistoriqueProjet($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectUnionHistoriqueMesure_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = ['maven_key' => static::$mavenKey, 'limit' => 5];
    $result = $repo->selectUnionHistoriqueMesure($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectUnionHistoriqueAnomalie_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = ['maven_key' => static::$mavenKey, 'limit' => 5];
    $result = $repo->selectUnionHistoriqueAnomalie($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectUnionHistoriqueDetails_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = ['maven_key' => static::$mavenKey, 'limit' => 5];
    $result = $repo->selectUnionHistoriqueDetails($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectHistoriqueAnomalieGraphique_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = ['maven_key' => static::$mavenKey, 'limit' => 5];
    $result = $repo->selectHistoriqueAnomalieGraphique($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testInsertHistoriqueAjoutProjet_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
                    ->setConstructorArgs([$registry])->onlyMethods(['getEntityManager', 'handleDatabaseException'])->getMock();

    // 6) Quand handleDatabaseException() est appelé avec notre exception, on renvoie ce tableau
    $expected = ['code' => 500, 'erreur' => static::$gestionTest];
    $repo->expects($this->once())->method('handleDatabaseException')
            ->with($fakeException)
            ->willReturn($expected);

    // 7) getEntityManager() renvoie notre EM stub
    $repo->method('getEntityManager')->willReturn($emStub);

    // 8) Prépare un jeu de données minimal pour le $map
    $map = [
        'maven_key' => static::$mavenKey,
        'analyse_key' => 'AZCc05qWgfifxdiJPzns',
        'version' => '1.2.0-RELEASE',
        'date_version' => '2024-07-12 16:34:46',
        'nom_projet' => 'ma-moulinette',
        'version_release' => '0',
        'version_snapshot' => '0',
        'version_autre' => '1',
        'suppress_warning' => '8',
        'no_sonar' => '0',
        'todo' => '17',
        'logger_info' => '14',
        'logger_warn' => '0',
        'logger_error' => '15',
        'logger_debug' => '8',
        'nombre_ligne' => '17049',
        'nombre_ligne_code' => '8928',
        'files' => '180',
        'functions' => '226',
        'classes' => '123',
        'functions' => '457',
        'coverage' => '50.1',
        'duplicated_lines_density' => '0.2',
        'sqale_debt_ratio' => '1',
        'tests' => '55',
        'violations' => '295',
        'dette' => '3054',
        'nombre_bug' => '88',
        'nombre_vulnerability' => '9',
        'nombre_code_smell' => '198',
        'bug_blocker' => '7',
        'bug_critical' => '0',
        'bug_major' => '44',
        'bug_minor' => '0',
        'bug_info' => '37',
        'vulnerability_blocker' => '0',
        'vulnerability_critical' => '9',
        'vulnerability_major' => '0',
        'vulnerability_minor' => '0',
        'vulnerability_info' => '0',
        'code_smell_blocker' => '0',
        'code_smell_critical' => '4',
        'code_smell_major' => '109',
        'code_smell_minor' => '13',
        'code_smell_info' => '72',
        'frontend' => '21',
        'backend' => '136',
        'autre' => '0',
        'inconnue' => '10',
        'nombre_anomalie_bloquant' => '7',
        'nombre_anomalie_critique' => '13',
        'nombre_anomalie_majeur' => '153',
        'nombre_anomalie_mineur' => '13',
        'nombre_anomalie_info' => '109',
        'note_reliability' => 'E',
        'note_security' => 'D',
        'note_sqale' => 'A',
        'note_hotspot' => 'A',
        'nombre_hotspot' => '0',
        'hotspot_high' => '0',
        'hotspot_medium' => '0',
        'hotspot_low' => '0',
        'initial' => 'true',
        'mode_collecte' => 'COLLECTE',
        'utilisateur_collecte' => 'admin@ma-moulinette.fr',
        'date_enregistrement' => new \DateTimeImmutable('2024-06-28 17:55:45+02')];
    $json='';
    // 9) Appel de la méthode : elle doit entrer dans le catch et renvoyer $expected
    $result = $repo->insertHistoriqueAjoutProjet($map, $json);

    $this->assertSame($expected, $result);
    }

    public function testSelectHistoriqueProjetByDate_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $result = $repo->selectHistoriqueProjetByDate($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectHistoriqueProjetLast_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $result = $repo->selectHistoriqueProjetLast($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectHistoriqueProjetReference_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $result = $repo->selectHistoriqueProjetReference($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectHistoriqueProjetFavori_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = ['liste_projet' => static::$mavenKey, 'nombre_projet_favori' => 5];
    $result = $repo->selectHistoriqueProjetFavori($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectHistoriqueIsValide_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $result = $repo->selectHistoriqueIsValide($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }

    public function testSelectHistoriqueIndicateurs_WhenSQLException(): void
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
    $repo = $this->getMockBuilder(HistoriqueRepository::class)
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
    $map = static::$mavenKey;
    $result = $repo->selectHistoriqueIndicateurs($map);

    // 9) Vérification
    $this->assertSame($expected, $result);
    }
    //selectHistoriqueIndicateurs
}
