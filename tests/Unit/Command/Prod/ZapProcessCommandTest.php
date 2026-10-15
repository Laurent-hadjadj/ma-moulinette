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

namespace App\Tests\Unit\Command\Prod;

use App\Command\Prod\ZapProcessCommand;
use App\Entity\{ZapProcessingQueue, ZapScan};
use App\Exception\Zap\ZapGzipDecodeException;
use App\Repository\ZapProcessingQueueRepository;
use App\Service\Zap\ZapIngester;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-10-13 : test Unit du worker court ZAP, sur le gabarit
 * DependencyCheckProcessCommandTest.
 *
 * Contrairement à Dependency-Check, l'Ingester n'est pas optionnel ici (pas
 * de "phase 1 fallback" légataire) — il est systématiquement mocké.
 *
 * Couvre :
 *  - queue vide -> exit 0, 0 traite
 *  - 1 row valide -> done
 *  - 1 row payload corrompu -> requeue (attempts < 3) puis failed (attempts >= 3)
 *  - reclaim stale -> increment requeued
 */
#[AllowMockObjectsWithoutExpectations]
class ZapProcessCommandTest extends TestCase
{
    /** @var ZapProcessingQueueRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $repo;
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;
    /** @var ZapIngester&\PHPUnit\Framework\MockObject\MockObject */
    private $ingester;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->repo     = $this->createMock(ZapProcessingQueueRepository::class);
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->ingester = $this->createMock(ZapIngester::class);
        $this->logger   = $this->createMock(LoggerInterface::class);

        $cmd = new ZapProcessCommand($this->em, $this->repo, $this->ingester, $this->logger);
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
        $fakeScan = $this->makeScan(99);

        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        $this->repo->expects($this->once())->method('find')->with(1)->willReturn($entry);
        $this->ingester->method('ingest')->willReturn($fakeScan);

        $exitCode = $this->tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(ZapProcessingQueue::STATUS_DONE, $entry->getStatus());
        $this->assertNotNull($entry->getProcessedAt());
        $this->assertNull($entry->getErrorMessage());
        $this->assertStringContainsString('done', $this->tester->getDisplay());
    }

    public function testRequeuesOnIngesterFailureIfAttemptsBelowThreshold(): void
    {
        $entry = $this->createEntry(2, attempts: 1);

        $this->repo->method('reclaimStaleProcessing')->willReturn(0);
        $this->repo->method('claimNextBatch')->willReturn([$entry]);
        $this->repo->expects($this->once())->method('find')->with(2)->willReturn($entry);
        $this->ingester->method('ingest')->willThrowException(new ZapGzipDecodeException('Decompression gzip échouée.'));

        $this->tester->execute([]);

        $this->assertSame(ZapProcessingQueue::STATUS_QUEUED, $entry->getStatus(), 'requeue car attempts=1 < 3');
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
        $this->ingester->method('ingest')->willThrowException(new ZapGzipDecodeException('Decompression gzip échouée.'));

        $exitCode = $this->tester->execute([]);

        $this->assertSame(ZapProcessingQueue::STATUS_FAILED, $entry->getStatus());
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
    }

    private function createEntry(int $id, int $attempts = 0): ZapProcessingQueue
    {
        $entry = (new ZapProcessingQueue())
            ->setUlid(str_pad((string) $id, 26, '0', STR_PAD_LEFT))
            ->setPayloadGz(gzencode('{}', 6))
            ->setPayloadSha256(str_repeat((string) $id, 64))
            ->setPayloadSize(2)
            ->setContentType(ZapProcessingQueue::CONTENT_JSON)
            ->setMavenKey('fr.ma-moulinette:ma-moulinette')
            ->setProjectVersion('1.0')
            ->setStatus(ZapProcessingQueue::STATUS_PROCESSING)
            ->setAttempts($attempts);
        $this->setEntityId($entry, $id);
        return $entry;
    }

    private function makeScan(int $id): ZapScan
    {
        $scan = (new ZapScan())
            ->setMavenKey('fr.ma-moulinette:ma-moulinette')
            ->setProjectVersion('1.0')
            ->setGeneratedAt(new \DateTimeImmutable());
        $ref = new \ReflectionProperty(ZapScan::class, 'id');
        $ref->setValue($scan, $id);
        return $scan;
    }

    private function setEntityId(ZapProcessingQueue $entry, int $id): void
    {
        $ref = new \ReflectionProperty(ZapProcessingQueue::class, 'id');
        $ref->setValue($entry, $id);
    }
}
