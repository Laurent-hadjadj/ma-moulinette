<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\NpmProcessingQueue;
use App\Repository\NpmProcessingQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Couvre les méthodes ORM-only de NpmProcessingQueueRepository
 * (claimNextBatch, reclaimStaleProcessing, purgeTerminatedBefore,
 * countByStatus, findByPayloadSha256, findByUlid) contre une vraie base
 * PostgreSQL de test.
 */
class NpmProcessingQueueRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private NpmProcessingQueueRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(NpmProcessingQueue::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_processing_queue');
    }

    private function makeEntry(
        string $ulid,
        string $status = NpmProcessingQueue::STATUS_QUEUED,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $startedAt = null
    ): NpmProcessingQueue {
        $entry = (new NpmProcessingQueue())
            ->setUlid($ulid)
            ->setPayloadGz('gzip-fake')
            ->setPayloadSha256(hash('sha256', $ulid))
            ->setPayloadSize(9)
            ->setContentType(NpmProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('angular-demo')
            ->setProjectVersion('1.0.0')
            ->setStatus($status);

        if ($createdAt !== null) {
            $entry->setCreatedAt($createdAt);
        }
        if ($startedAt !== null) {
            $entry->setStartedAt($startedAt);
        }

        $this->em->persist($entry);
        return $entry;
    }

    public function testFindByPayloadSha256ReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repo->findByPayloadSha256('inexistant'));
    }

    public function testFindByPayloadSha256ReturnsEntity(): void
    {
        $entry = $this->makeEntry('01AAAAAAAAAAAAAAAAAAAAAAAA');
        $this->em->flush();

        $found = $this->repo->findByPayloadSha256($entry->getPayloadSha256());

        $this->assertNotNull($found);
        $this->assertSame('01AAAAAAAAAAAAAAAAAAAAAAAA', $found->getUlid());
    }

    public function testFindByUlidReturnsEntity(): void
    {
        $this->makeEntry('01BBBBBBBBBBBBBBBBBBBBBBBB');
        $this->em->flush();

        $found = $this->repo->findByUlid('01BBBBBBBBBBBBBBBBBBBBBBBB');

        $this->assertNotNull($found);
        $this->assertSame('01BBBBBBBBBBBBBBBBBBBBBBBB', $found->getUlid());
    }

    public function testFindByUlidReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repo->findByUlid('inconnu'));
    }

    public function testClaimNextBatchReturnsEmptyArrayWhenQueueIsEmpty(): void
    {
        $this->assertSame([], $this->repo->claimNextBatch(5));
    }

    public function testClaimNextBatchClaimsQueuedRowsAndMarksProcessing(): void
    {
        $this->makeEntry('01CCCCCCCCCCCCCCCCCCCCCCCC');
        $this->makeEntry('01DDDDDDDDDDDDDDDDDDDDDDDD');
        $this->em->flush();

        $claimed = $this->repo->claimNextBatch(5);

        $this->assertCount(2, $claimed);
        foreach ($claimed as $entity) {
            $this->assertSame(NpmProcessingQueue::STATUS_PROCESSING, $entity->getStatus());
            $this->assertNotNull($entity->getStartedAt());
            $this->assertSame(1, $entity->getAttempts());
        }
    }

    public function testClaimNextBatchRespectsBatchSizeLimit(): void
    {
        $this->makeEntry('01EEEEEEEEEEEEEEEEEEEEEEEE');
        $this->makeEntry('01FFFFFFFFFFFFFFFFFFFFFFFF');
        $this->em->flush();

        $claimed = $this->repo->claimNextBatch(1);

        $this->assertCount(1, $claimed);
    }

    public function testReclaimStaleProcessingRequeuesOldRows(): void
    {
        $old = (new \DateTimeImmutable())->modify('-1 hour');
        $this->makeEntry('01GGGGGGGGGGGGGGGGGGGGGGGG', NpmProcessingQueue::STATUS_PROCESSING, null, $old);
        $this->em->flush();

        $reclaimed = $this->repo->reclaimStaleProcessing(300);

        $this->assertSame(1, $reclaimed);

        $this->em->clear();
        $refreshed = $this->repo->findByUlid('01GGGGGGGGGGGGGGGGGGGGGGGG');
        $this->assertSame(NpmProcessingQueue::STATUS_QUEUED, $refreshed->getStatus());
        $this->assertNull($refreshed->getStartedAt());
    }

    public function testReclaimStaleProcessingIgnoresRecentRows(): void
    {
        $this->makeEntry('01HHHHHHHHHHHHHHHHHHHHHHHH', NpmProcessingQueue::STATUS_PROCESSING, null, new \DateTimeImmutable());
        $this->em->flush();

        $this->assertSame(0, $this->repo->reclaimStaleProcessing(300));
    }

    public function testPurgeTerminatedBeforeDryRunCountsWithoutDeleting(): void
    {
        $old = (new \DateTimeImmutable())->modify('-60 days');
        $this->makeEntry('01IIIIIIIIIIIIIIIIIIIIIIII', NpmProcessingQueue::STATUS_DONE, $old);
        $this->em->flush();

        $threshold = (new \DateTimeImmutable())->modify('-30 days');
        $count = $this->repo->purgeTerminatedBefore($threshold, true);

        $this->assertSame(1, $count);
        $this->assertNotNull($this->repo->findByUlid('01IIIIIIIIIIIIIIIIIIIIIIII'));
    }

    public function testPurgeTerminatedBeforeDeletesMatchingRows(): void
    {
        $old = (new \DateTimeImmutable())->modify('-60 days');
        $this->makeEntry('01JJJJJJJJJJJJJJJJJJJJJJJJ', NpmProcessingQueue::STATUS_FAILED, $old);
        $this->makeEntry('01KKKKKKKKKKKKKKKKKKKKKKKK', NpmProcessingQueue::STATUS_QUEUED, $old);
        $this->em->flush();

        $threshold = (new \DateTimeImmutable())->modify('-30 days');
        $deleted = $this->repo->purgeTerminatedBefore($threshold, false);

        $this->assertSame(1, $deleted);
        $this->em->clear();
        $this->assertNull($this->repo->findByUlid('01JJJJJJJJJJJJJJJJJJJJJJJJ'));
        $this->assertNotNull($this->repo->findByUlid('01KKKKKKKKKKKKKKKKKKKKKKKK'));
    }

    public function testCountByStatusReturnsCountsPerStatus(): void
    {
        $this->makeEntry('01LLLLLLLLLLLLLLLLLLLLLLLL', NpmProcessingQueue::STATUS_QUEUED);
        $this->makeEntry('01MMMMMMMMMMMMMMMMMMMMMMMM', NpmProcessingQueue::STATUS_DONE);
        $this->makeEntry('01NNNNNNNNNNNNNNNNNNNNNNNN', NpmProcessingQueue::STATUS_DONE);
        $this->em->flush();

        $counts = $this->repo->countByStatus();

        $this->assertSame(1, $counts[NpmProcessingQueue::STATUS_QUEUED]);
        $this->assertSame(0, $counts[NpmProcessingQueue::STATUS_PROCESSING]);
        $this->assertSame(2, $counts[NpmProcessingQueue::STATUS_DONE]);
        $this->assertSame(0, $counts[NpmProcessingQueue::STATUS_FAILED]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
