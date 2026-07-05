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

use App\Repository\OwaspTop10Repository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Exception as DBALException;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-05-08 : suppression de
 * `withAnyParameters()` et `with($this->isString())` (deprecated PHPUnit 14
 * sans `expects()`), et bascule des mocks "stub-only" (Statement,
 * EntityManager, ManagerRegistry, DBAL Exception) vers `createStub()` pour
 * éteindre les notices "No expectations were configured for the mock object". */

/**
 * [Description OwaspTop10RepositoryHandlerTest]
 */
class OwaspTop10RepositoryHandlerTest extends TestCase
{

    /**
     * [Description for testSelectOwaspTop10Referential_WhenSQLException]
     *
     * @return void
     *
     * Created at: 07/07/2025 09:02:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testSelectOwaspTop10Referential_WhenSQLException(): void
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
        $repo = $this->getMockBuilder(OwaspTop10Repository::class)
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
        $map = [ 'referential_version' => 2017 ];
        $result = $repo->selectOwaspTop10Referential($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

    /**
     * [Description for testSelectOwaspTop10Details_WhenSQLException]
     *
     * @return void
     *
     * Created at: 07/07/2025 09:03:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testSelectOwaspTop10Details_WhenSQLException(): void
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
        $repo = $this->getMockBuilder(OwaspTop10Repository::class)
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
        $map = [ 'menace' => 1 ];
        $result = $repo->selectOwaspTop10Details($map);

        // 9) Vérification
        $this->assertSame($expected, $result);
    }

}
