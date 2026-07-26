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
use App\Repository\BatchExecutionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Couvre BatchExecutionRepository contre la base de test réelle :
 * selectBatchExecutionLastTraitementId, deleteTraitement et le mapping
 * d'erreurs de handleDatabaseException().
 */
#[AllowMockObjectsWithoutExpectations]
class BatchExecutionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private BatchExecutionRepository $repo;

    /** @var array<int, Ulid> */
    private array $createdTraitementIds = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(BatchExecution::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdTraitementIds as $traitementId) {
            foreach ($this->repo->findBy(['traitementId' => $traitementId]) as $execution) {
                $this->em->remove($execution);
            }
        }
        $this->em->flush();
        parent::tearDown();
    }

    private function persistExecution(Ulid $traitementId, \DateTimeImmutable $dateEnregistrement): BatchExecution
    {
        $this->createdTraitementIds[] = $traitementId;

        $execution = new BatchExecution(
            'BatchExecutionRepositoryTest',
            new Ulid(),
            $traitementId,
            'admin@ma-moulinette.fr',
            '[COLLECTE]'
        );
        $execution->setDateEnregistrement($dateEnregistrement);

        $this->em->persist($execution);
        $this->em->flush();

        return $execution;
    }

    public function testSelectBatchExecutionLastTraitementIdReturnsMostRecentRow(): void
    {
        $traitementId = new Ulid();
        $older = $this->persistExecution($traitementId, new \DateTimeImmutable('-1 day'));
        $recent = $this->persistExecution($traitementId, new \DateTimeImmutable());

        $result = $this->repo->selectBatchExecutionLastTraitementId($traitementId->toRfc4122());

        $this->assertSame(200, $result['code']);
        $this->assertSame($recent->getId(), $result['id']);
        $this->assertNotSame($older->getId(), $result['id']);
    }

    public function testDeleteTraitementRemovesAllRowsForTraitementId(): void
    {
        $traitementId = new Ulid();
        $this->persistExecution($traitementId, new \DateTimeImmutable());
        $this->persistExecution($traitementId, new \DateTimeImmutable());

        $before = $this->repo->findBy(['traitementId' => $traitementId]);
        $this->assertCount(2, $before);

        $result = $this->repo->deleteTraitement($traitementId->toRfc4122());

        $this->assertSame(['code' => 200, 'erreur' => ''], $result);
        $this->em->clear();
        $this->assertCount(0, $this->repo->findBy(['traitementId' => $traitementId]));
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
