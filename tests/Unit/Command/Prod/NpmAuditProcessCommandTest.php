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

namespace App\Tests\Unit\Command\Prod;

use App\Command\Prod\NpmAuditProcessCommand;
use App\Entity\{NpmProcessingQueue, NpmScan};
use App\Repository\NpmProcessingQueueRepository;
use App\Service\NpmAudit\NpmAuditIngester;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test Unit du worker court — contrairement à DependencyCheckProcessCommand,
 * NpmAuditProcessCommand n'a pas de "phase 1 fallback" (NpmAuditIngester
 * est requis au constructeur, jamais null) : on mocke directement
 * l'ingester plutôt que de décompresser/parser un vrai payload.
 *
 * Couvre :
 *  - queue vide -> exit 0, 0 traité
 *  - 1 row -> ingester OK -> done
 *  - 1 row -> ingester lève -> requeue (attempts < 3) puis failed (attempts >= 3)
 *  - reclaim stale -> increment requeued
 *  - batch size (custom + clamp minimum)
 */
#[AllowMockObjectsWithoutExpectations]
class NpmAuditProcessCommandTest extends TestCase
{
    /** @var NpmProcessingQueueRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $repo;
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;
    /** @var NpmAuditIngester&\PHPUnit\Framework\MockObject\MockObject */
    private $ingester;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->repo     = $this->createMock(NpmProcessingQueueRepository::class);
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->ingester = $this->createMock(NpmAuditIngester::class);
        $this->logger   = $this->createMock(LoggerInterface::class);

        $cmd = new NpmAuditProcessCommand($this->em, $this->repo, $this->ingester, $this->logger);
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
    }

    public function testProcessesValidRowToDone(): void
    {
        $entry = $this->createEntry(1);
        $scan = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('angular-demo')->setProjectVersion('1.0')
            ->setScanDate(new \DateTimeImmutable());

        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($entry);
        $this->ingester->method('ingest')->willReturn($scan);

        $exitCode = $this->tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(NpmProcessingQueue::STATUS_DONE, $entry->getStatus());
        $this->assertNotNull($entry->getProcessedAt());
        $this->assertNull($entry->getErrorMessage());
        $this->assertStringContainsString('done', $this->tester->getDisplay());
    }

    public function testRequeuesOnIngestFailureIfAttemptsBelowThreshold(): void
    {
        $entry = $this->createEntry(2, attempts: 1);

        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        $this->repo->expects($this->once())->method('find')->with(2)->willReturn($entry);
        $this->ingester->method('ingest')->willThrowException(new \RuntimeException('payload corrompu'));

        $this->tester->execute([]);

        $this->assertSame(NpmProcessingQueue::STATUS_QUEUED, $entry->getStatus(), 'requeue car attempts=1 < 3');
        $this->assertNull($entry->getStartedAt(), 'started_at reset au requeue');
        $this->assertNotNull($entry->getErrorMessage());
        $this->assertStringContainsString('requeue', $this->tester->getDisplay());
    }

    public function testFailsDefinitivelyAtThirdAttempt(): void
    {
        $entry = $this->createEntry(3, attempts: 3);

        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        $this->repo->expects($this->once())->method('find')->with(3)->willReturn($entry);
        $this->ingester->method('ingest')->willThrowException(new \RuntimeException('payload corrompu'));

        $exitCode = $this->tester->execute([]);

        $this->assertSame(NpmProcessingQueue::STATUS_FAILED, $entry->getStatus());
        $this->assertNotNull($entry->getProcessedAt());
        $this->assertNotNull($entry->getErrorMessage());
        $this->assertStringContainsString('FAILED', $this->tester->getDisplay());
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testReportsRequeuedStaleCount(): void
    {
        $this->repo->method('reclaimStaleProcessing')->willReturn(7);
        $this->repo->method('claimNextBatch')->willReturn([]);

        $this->tester->execute([]);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('7 row', $output);
        $this->assertStringContainsString('requeu', $output);
    }

    public function testCustomBatchSizeIsHonored(): void
    {
        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->expects($this->once())
            ->method('claimNextBatch')
            ->with(10)
            ->willReturn([]);

        $this->tester->execute(['--batch' => 10]);
    }

    public function testEnforcesMinimumBatchSize(): void
    {
        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->expects($this->once())
            ->method('claimNextBatch')
            ->with(1)
            ->willReturn([]);

        $this->tester->execute(['--batch' => 0]);
    }

    /**
     * Throttle du heartbeat "queue vide" (cf. logHeartbeatIfDue) : seulement
     * aux minutes multiples de 15, pour ne pas noyer cron.log une fois les
     * queues réellement actives.
     */
    public function testHeartbeatIsDueOnlyEveryFifteenMinutes(): void
    {
        $method = new \ReflectionMethod(NpmAuditProcessCommand::class, 'isHeartbeatDue');

        foreach ([0, 15, 30, 45] as $minute) {
            $this->assertTrue($method->invoke(null, $minute), "minute $minute doit déclencher le heartbeat");
        }
        foreach ([1, 7, 14, 16, 29, 44, 59] as $minute) {
            $this->assertFalse($method->invoke(null, $minute), "minute $minute ne doit pas déclencher le heartbeat");
        }
    }

    private function createEntry(int $id, int $attempts = 0): NpmProcessingQueue
    {
        $entry = (new NpmProcessingQueue())
            ->setUlid(str_pad((string) $id, 26, '0', STR_PAD_LEFT))
            ->setPayloadGz('gz-ignored-car-ingester-mocke')
            ->setPayloadSha256(str_repeat((string) $id, 64))
            ->setPayloadSize(10)
            ->setContentType(NpmProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('angular-demo')
            ->setProjectVersion('1.0')
            ->setStatus(NpmProcessingQueue::STATUS_PROCESSING)
            ->setAttempts($attempts);
        $ref = new \ReflectionProperty(NpmProcessingQueue::class, 'id');
        $ref->setValue($entry, $id);
        return $entry;
    }
}
