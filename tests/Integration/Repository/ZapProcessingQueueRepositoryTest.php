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

namespace App\Tests\Integration\Repository;

use App\Entity\ZapProcessingQueue;
use App\Repository\ZapProcessingQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * MODIF 2026-10-18 : couvre les méthodes ORM-only de ZapProcessingQueueRepository
 * (claimNextBatch, reclaimStaleProcessing, purgeTerminatedBefore, countByStatus,
 * findByPayloadSha256, findByUlid) contre une vraie base PostgreSQL de test —
 * ces méthodes ne sont pas mockables proprement (QueryBuilder/DQL réels).
 */
class ZapProcessingQueueRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ZapProcessingQueueRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(ZapProcessingQueue::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_processing_queue');
    }

    private function makeEntry(
        string $ulid,
        string $status = ZapProcessingQueue::STATUS_QUEUED,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $startedAt = null
    ): ZapProcessingQueue {
        $entry = (new ZapProcessingQueue())
            ->setUlid($ulid)
            ->setPayloadGz('gzip-fake')
            ->setPayloadSha256(hash('sha256', $ulid))
            ->setPayloadSize(9)
            ->setContentType('application/json')
            ->setMavenKey('fr.ma-moulinette:ma-moulinette')
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
            $this->assertSame(ZapProcessingQueue::STATUS_PROCESSING, $entity->getStatus());
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
        $this->makeEntry('01GGGGGGGGGGGGGGGGGGGGGGGG', ZapProcessingQueue::STATUS_PROCESSING, null, $old);
        $this->em->flush();

        $reclaimed = $this->repo->reclaimStaleProcessing(300);

        $this->assertSame(1, $reclaimed);

        $this->em->clear();
        $refreshed = $this->repo->findByUlid('01GGGGGGGGGGGGGGGGGGGGGGGG');
        $this->assertSame(ZapProcessingQueue::STATUS_QUEUED, $refreshed->getStatus());
        $this->assertNull($refreshed->getStartedAt());
    }

    public function testReclaimStaleProcessingIgnoresRecentRows(): void
    {
        $this->makeEntry('01HHHHHHHHHHHHHHHHHHHHHHHH', ZapProcessingQueue::STATUS_PROCESSING, null, new \DateTimeImmutable());
        $this->em->flush();

        $this->assertSame(0, $this->repo->reclaimStaleProcessing(300));
    }

    public function testPurgeTerminatedBeforeDryRunCountsWithoutDeleting(): void
    {
        $old = (new \DateTimeImmutable())->modify('-60 days');
        $this->makeEntry('01IIIIIIIIIIIIIIIIIIIIIIII', ZapProcessingQueue::STATUS_DONE, $old);
        $this->em->flush();

        $threshold = (new \DateTimeImmutable())->modify('-30 days');
        $count = $this->repo->purgeTerminatedBefore($threshold, true);

        $this->assertSame(1, $count);
        $this->assertNotNull($this->repo->findByUlid('01IIIIIIIIIIIIIIIIIIIIIIII'));
    }

    public function testPurgeTerminatedBeforeDeletesMatchingRows(): void
    {
        $old = (new \DateTimeImmutable())->modify('-60 days');
        $this->makeEntry('01JJJJJJJJJJJJJJJJJJJJJJJJ', ZapProcessingQueue::STATUS_FAILED, $old);
        $this->makeEntry('01KKKKKKKKKKKKKKKKKKKKKKKK', ZapProcessingQueue::STATUS_QUEUED, $old);
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
        $this->makeEntry('01LLLLLLLLLLLLLLLLLLLLLLLL', ZapProcessingQueue::STATUS_QUEUED);
        $this->makeEntry('01MMMMMMMMMMMMMMMMMMMMMMMM', ZapProcessingQueue::STATUS_DONE);
        $this->makeEntry('01NNNNNNNNNNNNNNNNNNNNNNNN', ZapProcessingQueue::STATUS_DONE);
        $this->em->flush();

        $counts = $this->repo->countByStatus();

        $this->assertSame(1, $counts[ZapProcessingQueue::STATUS_QUEUED]);
        $this->assertSame(0, $counts[ZapProcessingQueue::STATUS_PROCESSING]);
        $this->assertSame(2, $counts[ZapProcessingQueue::STATUS_DONE]);
        $this->assertSame(0, $counts[ZapProcessingQueue::STATUS_FAILED]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
