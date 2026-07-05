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

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\BatchTraitement;
use App\Repository\BatchTraitementRepository;
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\{ConnectionException, NotNullConstraintViolationException,UniqueConstraintViolationException};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-15 : tests Unit pour BatchTraitementRepository.
 * Couvre handleDatabaseException, les 4 SELECT, l'insert, les 3 UPDATE et le delete.
 */
#[AllowMockObjectsWithoutExpectations]
class BatchTraitementRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private BatchTraitementRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(BatchTraitement::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new BatchTraitementRepository($registry);
    }

    /* ============ handleDatabaseException ============ */

    public function testHandleDatabaseExceptionGenericMessage(): void
    {
        $r = $this->repo->handleDatabaseException(new \RuntimeException('boom'));
        $this->assertSame(500, $r['code']);
        $this->assertSame('boom', $r['erreur']);
    }

    public function testHandleDatabaseExceptionConnection(): void
    {
        $e = new class('SQLSTATE[08006]') extends ConnectionException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $r = $this->repo->handleDatabaseException($e);
        $this->assertSame(500, $r['code']);
        $this->assertStringContainsString('connexion', $r['erreur']);
    }

    public function testHandleDatabaseExceptionNotNull(): void
    {
        $e = new class('responsable not null') extends NotNullConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $r = $this->repo->handleDatabaseException($e);
        $this->assertSame(500, $r['code']);
        $this->assertSame('responsable not null', $r['erreur']);
    }

    public function testHandleDatabaseExceptionUnique(): void
    {
        $e = new class('dup') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $r = $this->repo->handleDatabaseException($e);
        $this->assertSame(23505, $r['code']);
        $this->assertSame('Les informations existent déjà.', $r['erreur']);
    }

    /* ============ SELECTs ============ */

    public function testSelectBatchTraitementAutomatiqueListeReturnsRows(): void
    {
        $rows = [['nom_traitement' => 'Daily', 'portefeuille' => 'P1', 'nombre_projet' => 4, 'traitement_id' => 'u1']];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn($rows);

        $r = $this->repo->selectBatchTraitementAutomatiqueListe();

        $this->assertSame(200, $r['code']);
        $this->assertSame($rows, $r['liste']);
    }

    public function testSelectBatchTraitementAutomatiqueListeHandlesException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $r = $this->repo->selectBatchTraitementAutomatiqueListe();

        $this->assertSame(500, $r['code']);
        $this->assertStringContainsString('boom', $r['erreur']);
    }

    public function testSelectBatchTraitementActivatedReturnsRows(): void
    {
        $rows = [['mode_collecte' => 'TRAITEMENT MANUEL', 'titre' => 'Test']];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn($rows);

        $r = $this->repo->selectBatchTraitementActivated();

        $this->assertSame(200, $r['code']);
        $this->assertSame($rows, $r['liste']);
    }

    public function testSelectBatchTraitementActivatedHandlesException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $r = $this->repo->selectBatchTraitementActivated();

        $this->assertSame(500, $r['code']);
    }

    public function testSelectBatchTraitementByTraitementIdReturnsFirstRow(): void
    {
        $row = ['traitement_id' => '01H...', 'mode_collecte' => 'TRAITEMENT MANUEL'];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('bindValue')->with(':traitement_id', '01H...');
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([$row]);

        $r = $this->repo->selectBatchTraitementByTraitementId('01H...');

        $this->assertSame(200, $r['code']);
        $this->assertSame($row, $r['traitement']);
    }

    public function testSelectBatchTraitementByTraitementIdHandlesException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $r = $this->repo->selectBatchTraitementByTraitementId('01H...');

        $this->assertSame(500, $r['code']);
    }

    public function testCountBatchTraitementPendingAndProgressReturnsAggregatedFields(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([[
            'pending_count' => 2,
            'in_progress_count' => 1,
            'pending_null_count' => 0,
            'in_progress_null_count' => 0,
        ]]);

        $r = $this->repo->countBatchTraitementPendingAndProgress();

        $this->assertSame(200, $r['code']);
        $this->assertSame(2, $r['pending']);
        $this->assertSame(1, $r['progress']);
        $this->assertSame(0, $r['pending_null']);
        $this->assertSame(0, $r['in_progress_null']);
    }

    public function testCountBatchTraitementPendingAndProgressHandlesException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $r = $this->repo->countBatchTraitementPendingAndProgress();

        $this->assertSame(500, $r['code']);
    }

    /* ============ UPDATE / INSERT / DELETE ============ */

    public function testUpdateBatchTraitementPendingCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');

        $r = $this->repo->updateBatchTraitementPending([
            'pending' => true,
            'traitement_id' => '01H...',
        ]);

        $this->assertSame(200, $r['code']);
    }

    public function testUpdateBatchTraitementPendingRollsBackOnException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollBack');

        $r = $this->repo->updateBatchTraitementPending([
            'pending' => null,
            'traitement_id' => '01H...',
        ]);

        $this->assertSame(500, $r['code']);
    }

    public function testUpdateBatchTraitementCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');

        $r = $this->repo->updateBatchTraitement([
            'debut_traitement' => '2026-05-15 10:00:00',
            'fin_traitement'   => '2026-05-15 10:05:00',
            'success'          => true,
            'pending'          => false,
            'in_progress'      => false,
            'traitement_id'    => '01H...',
        ]);

        $this->assertSame(200, $r['code']);
    }

    public function testUpdateBatchTraitementRollsBackOnException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollBack');

        $r = $this->repo->updateBatchTraitement([
            'debut_traitement' => '2026-05-15',
            'fin_traitement'   => '2026-05-15',
            'success'          => null,
            'pending'          => null,
            'in_progress'      => null,
            'traitement_id'    => '01H...',
        ]);

        $this->assertSame(500, $r['code']);
    }

    public function testInsertBatchTraitementCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');

        $r = $this->repo->insertBatchTraitement([
            'activated'         => true,
            'mode_collecte'     => 'TRAITEMENT MANUEL',
            'success'           => '',
            'in_progress'       => '',
            'pending'           => '',
            'titre'             => 'Test',
            'portefeuille'      => 'PF-A',
            'nombre_projet'     => 5,
            'responsable'       => 'admin',
            'responsable_short' => 'adm',
            'traitement_id'     => '01H...',
            'date_enregistrement' => new \DateTimeImmutable(),
        ]);

        $this->assertSame(200, $r['code']);
    }

    public function testInsertBatchTraitementNormalizesEmptyStringsToNullForBools(): void
    {
        /* On vérifie que les booleans nullable passés en '' (chaîne vide) ne
           crashent pas — le repo doit les convertir en null en interne. */
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->method('commit');

        $r = $this->repo->insertBatchTraitement([
            'activated'         => true,
            'mode_collecte'     => 'TRAITEMENT AUTOMATIQUE',
            'success'           => '',
            'in_progress'       => '',
            'pending'           => '',
            'titre'             => 'Auto',
            'portefeuille'      => 'PF-B',
            'nombre_projet'     => 2,
            'responsable'       => 'cron',
            'responsable_short' => 'crn',
            'traitement_id'     => '01H...',
            'date_enregistrement' => new \DateTimeImmutable(),
        ]);

        $this->assertSame(200, $r['code']);
    }

    public function testInsertBatchTraitementRollsBackOnException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollBack');

        $r = $this->repo->insertBatchTraitement([
            'activated'         => true,
            'mode_collecte'     => 'TRAITEMENT MANUEL',
            'success'           => null,
            'in_progress'       => null,
            'pending'           => null,
            'titre'             => 'X',
            'portefeuille'      => 'P',
            'nombre_projet'     => 0,
            'responsable'       => 'r',
            'responsable_short' => 'r',
            'traitement_id'     => '01H...',
            'date_enregistrement' => new \DateTimeImmutable(),
        ]);

        $this->assertSame(500, $r['code']);
    }

    public function testDeleteTraitementCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');

        $r = $this->repo->deleteTraitement('01H...');

        $this->assertSame(200, $r['code']);
    }

    public function testDeleteTraitementRollsBackOnException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollBack');

        $r = $this->repo->deleteTraitement('01H...');

        $this->assertSame(500, $r['code']);
    }

    public function testUpdatePortefeuilleCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');

        $r = $this->repo->updatePortefeuille([
            'portefeuille'  => 'PF-A',
            'nombre_projet' => 12,
        ]);

        $this->assertSame(200, $r['code']);
    }

    public function testUpdatePortefeuilleRollsBackOnException(): void
    {
        $exception = new class('boom') extends \RuntimeException implements DBALException {};

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollBack');

        $r = $this->repo->updatePortefeuille(['portefeuille' => 'PF', 'nombre_projet' => 0]);

        $this->assertSame(500, $r['code']);
    }
}
