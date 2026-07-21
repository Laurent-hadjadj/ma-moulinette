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

/* MODIF 2026-05-21 : déplacé dans tests/Unit/Command/Prod. */
namespace App\Tests\Unit\Command\Prod;

use App\Command\Prod\DependencyCheckProcessCommand;
use App\Entity\DcProcessingQueue;
use App\Repository\DcProcessingQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-05-08 : test Unit du worker court.
 *
 * Stratégie : mock du Repository et de l'EntityManager pour valider la logique
 * sans toucher la BDD. Couvre :
 *  - queue vide -> exit 0, 0 traite
 *  - 1 row valide -> done
 *  - 1 row payload corrompu -> requeue (attempts < 3) puis failed (attempts >= 3)
 *  - reclaim stale -> increment requeued
 */
#[AllowMockObjectsWithoutExpectations]
class DependencyCheckProcessCommandTest extends TestCase
{
    /** @var DcProcessingQueueRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $repo;
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private CommandTester $tester;

    private const VALID_JSON = '{"reportSchema":"1.1","projectInfo":{"groupID":"fr.ma-moulinette","artifactID":"ma-moulinette","version":"1.0"},"dependencies":[]}';

    protected function setUp(): void
    {
        $this->repo   = $this->createMock(DcProcessingQueueRepository::class);
        $this->em     = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $cmd = new DependencyCheckProcessCommand($this->em, $this->repo, $this->logger);
        $this->tester = new CommandTester($cmd);
    }

    public function testEmptyQueueExitsCleanly(): void
    {
        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([]);

        $exitCode = $this->tester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Done', $output);
        $this->assertStringContainsString('0', $output);
    }

    public function testProcessesValidRowToDone(): void
    {
        $entry = $this->createValidEntry(1);

        // claim renvoie 1 row -> on capture les IDs
        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        // refetch par ID
        // MODIF 2026-05-16 : expects($this->any()) ajoute pour PHPUnit 14.
        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($entry);

        $exitCode = $this->tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(DcProcessingQueue::STATUS_DONE, $entry->getStatus());
        $this->assertNotNull($entry->getProcessedAt());
        $this->assertNull($entry->getErrorMessage());
        $this->assertStringContainsString('done', $this->tester->getDisplay());
    }

    public function testRequeuesOnCorruptedPayloadIfAttemptsBelowThreshold(): void
    {
        $entry = $this->createCorruptedEntry(2, attempts: 1);

        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        // MODIF 2026-05-16 : expects($this->any()) ajoute pour PHPUnit 14.
        $this->repo->expects($this->once())->method('find')->with(2)->willReturn($entry);

        $this->tester->execute([]);

        $this->assertSame(DcProcessingQueue::STATUS_QUEUED, $entry->getStatus(), 'requeue car attempts=1 < 3');
        $this->assertNull($entry->getStartedAt(), 'started_at reset au requeue');
        $this->assertNotNull($entry->getErrorMessage());
        $this->assertStringContainsString('requeue', $this->tester->getDisplay());
    }

    public function testFailsDefinitivelyAtThirdAttempt(): void
    {
        $entry = $this->createCorruptedEntry(3, attempts: 3);

        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        // MODIF 2026-05-16 : expects($this->any()) ajoute pour PHPUnit 14.
        $this->repo->expects($this->once())->method('find')->with(3)->willReturn($entry);

        $exitCode = $this->tester->execute([]);

        $this->assertSame(DcProcessingQueue::STATUS_FAILED, $entry->getStatus());
        $this->assertNotNull($entry->getProcessedAt());
        $this->assertNotNull($entry->getErrorMessage());
        $this->assertStringContainsString('FAILED', $this->tester->getDisplay());
        // MODIF 2026-07-21 : un échec définitif doit se refléter dans le code retour.
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testReportsRequeuedStaleCount(): void
    {
        $this->repo->method('reclaimStaleProcessing')->willReturn(7);
        $this->repo->method('claimNextBatch')->willReturn([]);

        $this->tester->execute([]);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('7 row', $output);
        $this->assertStringContainsString('requeue', $output);
    }

    public function testCustomBatchSizeIsHonored(): void
    {
        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        // On vérifie que claimNextBatch est appelé avec batchSize=10
        $this->repo->expects($this->once())
            ->method('claimNextBatch')
            ->with(10)
            ->willReturn([]);

        $this->tester->execute(['--batch' => 10]);
    }

    public function testEnforcesMinimumBatchSize(): void
    {
        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        // batch=0 doit être clampe a 1
        $this->repo->expects($this->once())
            ->method('claimNextBatch')
            ->with(1)
            ->willReturn([]);

        $this->tester->execute(['--batch' => 0]);
    }

    // ═══════════════════ Helpers ═════════════════════════════════════════════

    /**
     * Cree une entity avec payload gz valide.
     */
    private function createValidEntry(int $id, int $attempts = 0): DcProcessingQueue
    {
        $entry = (new DcProcessingQueue())
            ->setUlid(str_pad((string) $id, 26, '0', STR_PAD_LEFT))
            ->setPayloadGz(gzencode(self::VALID_JSON, 6))
            ->setPayloadSha256(str_repeat((string) $id, 64))
            ->setPayloadSize(strlen(self::VALID_JSON))
            ->setContentType(DcProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('ma-moulinette')
            ->setProjectVersion('1.0')
            ->setStatus(DcProcessingQueue::STATUS_PROCESSING)
            ->setAttempts($attempts);
        $this->setEntityId($entry, $id);
        return $entry;
    }

    /**
     * Cree une entity avec payload gz corrompu (decompression KO).
     */
    private function createCorruptedEntry(int $id, int $attempts): DcProcessingQueue
    {
        $entry = (new DcProcessingQueue())
            ->setUlid(str_pad((string) $id, 26, 'b', STR_PAD_LEFT))
            ->setPayloadGz('not gzip data')
            ->setPayloadSha256(str_repeat('b', 64))
            ->setPayloadSize(13)
            ->setContentType(DcProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('test.bad')
            ->setProjectArtifact('corrupted')
            ->setProjectVersion('0.0.1')
            ->setStatus(DcProcessingQueue::STATUS_PROCESSING)
            ->setAttempts($attempts);
        $this->setEntityId($entry, $id);
        return $entry;
    }

    /**
     * Force un ID sur l’entité via reflection (Doctrine ne le fait pas en unit).
     */
    private function setEntityId(DcProcessingQueue $entry, int $id): void
    {
        $ref = new \ReflectionProperty(DcProcessingQueue::class, 'id');
        $ref->setValue($entry, $id);
    }
}
