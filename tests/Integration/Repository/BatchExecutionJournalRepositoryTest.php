<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\BatchExecution;
use App\Entity\BatchExecutionJournal;
use App\Repository\BatchExecutionJournalRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Couvre BatchExecutionJournalRepository contre la base de test réelle :
 * suppression, comptage par code, lecture par job, et le mapping d'erreurs
 * de handleDatabaseException().
 */
#[AllowMockObjectsWithoutExpectations]
class BatchExecutionJournalRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BatchExecutionJournalRepository $repo;
    private BatchExecution $job;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(BatchExecutionJournal::class);

        $this->job = new BatchExecution(
            'BatchExecJournalRepoTest',
            new Ulid(),
            new Ulid(),
            'admin@ma-moulinette.fr',
            '[COLLECTE]'
        );
        $this->em->persist($this->job);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        // Re-récupéré par ID : certains tests appellent em->clear(), qui détache
        // $this->job de l'EntityManager (remove() exige une entité managée).
        $job = $this->em->getRepository(BatchExecution::class)->find($this->job->getId());
        if ($job !== null) {
            $this->em->remove($job);
            $this->em->flush();
        }
        parent::tearDown();
    }

    private function persistJournalEntry(string $nomProjet, int $code): BatchExecutionJournal
    {
        $entry = new BatchExecutionJournal(
            nomProjet: $nomProjet,
            portefeuille: 'Equipe DEV',
            compteRendu: '<html>rapport</html>',
            batchExecution: $this->job,
            code: $code
        );
        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    private function jobId(): string
    {
        return (string) $this->job->getId();
    }

    public function testCountBatchExecutionJournalCodeAggregatesByStatus(): void
    {
        $this->persistJournalEntry('projet-a', 200);
        $this->persistJournalEntry('projet-b', 200);
        $this->persistJournalEntry('projet-c', 500);
        $this->persistJournalEntry('projet-d', 202);

        $result = $this->repo->countBatchExecutionJournalCode($this->jobId());

        $this->assertSame(200, $result['code']);
        $this->assertSame(2, $result['ok']);
        $this->assertSame(2, $result['ko']);
        $this->assertSame(1, $result['oko']);
    }

    public function testSelectBatchExecutionJournalNomProjetAndStatusReturnsSortedRows(): void
    {
        $this->persistJournalEntry('projet-z', 500);
        $this->persistJournalEntry('projet-a', 200);

        $result = $this->repo->selectBatchExecutionJournalNomProjetAndStatus($this->jobId());

        $this->assertSame(200, $result['code']);
        $this->assertCount(2, $result['liste']);
        // Triés par nom_projet ASC.
        $this->assertSame('projet-a', $result['liste'][0]['nom_projet']);
        $this->assertSame('ok', $result['liste'][0]['status']);
        $this->assertSame('projet-z', $result['liste'][1]['nom_projet']);
        $this->assertSame('ko', $result['liste'][1]['status']);
    }

    public function testSelectBatchExecutionJournalByJobReturnsCompteRendu(): void
    {
        $this->persistJournalEntry('projet-a', 200);

        $result = $this->repo->selectBatchExecutionJournalByJob([
            'job_id' => $this->jobId(),
            'nom_projet' => 'projet-a',
        ]);

        $this->assertSame(200, $result['code']);
        $this->assertCount(1, $result['journal']);
    }

    public function testDeleteJournalRemovesAllEntriesForJob(): void
    {
        $this->persistJournalEntry('projet-a', 200);
        $this->persistJournalEntry('projet-b', 200);

        $before = $this->repo->countBatchExecutionJournalCode($this->jobId());
        $this->assertSame(2, $before['ok']);

        $result = $this->repo->deleteJournal($this->jobId());

        $this->assertSame(['code' => 200, 'erreur' => ''], $result);
        $this->em->clear();
        $after = $this->repo->countBatchExecutionJournalCode($this->jobId());
        // SUM() sur un groupe vide renvoie NULL en SQL, pas 0.
        $this->assertNull($after['ok']);
    }

    public function testHandleDatabaseExceptionMapsUniqueConstraintViolation(): void
    {
        $exception = $this->createMock(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(['code' => 23505, 'erreur' => 'Les informations existent déjà.'], $result);
    }

    public function testHandleDatabaseExceptionMapsConnectionException(): void
    {
        $exception = $this->createMock(\Doctrine\DBAL\Exception\ConnectionException::class);

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('La connexion à la base de données a échoué.', $result['erreur']);
    }

    public function testHandleDatabaseExceptionFallsBackToGenericMessage(): void
    {
        $result = $this->repo->handleDatabaseException(new \RuntimeException('boom'));

        $this->assertSame(['code' => 500, 'erreur' => 'boom'], $result);
    }
}
