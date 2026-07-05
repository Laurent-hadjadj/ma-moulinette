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

use App\Command\Prod\DependencyCheckPurgeCommand;
use App\Repository\DcProcessingQueueRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-05-08 : test Unit de la commande de purge.
 */
#[AllowMockObjectsWithoutExpectations]
class DependencyCheckPurgeCommandTest extends TestCase
{
    /** @var DcProcessingQueueRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $repo;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->repo   = $this->createMock(DcProcessingQueueRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Stats par défaut (pour l'affichage post-purge)
        $this->repo->method('countByStatus')->willReturn([
            'queued' => 0, 'processing' => 0, 'done' => 5, 'failed' => 1,
        ]);

        $cmd = new DependencyCheckPurgeCommand($this->repo, $this->logger);
        $this->tester = new CommandTester($cmd);
    }

    public function testDryRunCallsRepoWithDryRunTrue(): void
    {
        $this->repo->expects($this->once())
            ->method('purgeTerminatedBefore')
            ->with($this->isInstanceOf(\DateTimeImmutable::class), true)
            ->willReturn(3);

        $exit = $this->tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exit);
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('3 row', $output);
        $this->assertStringContainsString('seraient', $output);
    }

    public function testRealRunCallsRepoWithDryRunFalse(): void
    {
        $this->repo->expects($this->once())
            ->method('purgeTerminatedBefore')
            ->with($this->isInstanceOf(\DateTimeImmutable::class), false)
            ->willReturn(7);

        $exit = $this->tester->execute([]);

        $this->assertSame(0, $exit);
        $output = $this->tester->getDisplay();
        $this->assertStringNotContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('7 row', $output);
        $this->assertStringContainsString('supprimée', $output);
    }

    public function testThresholdComputedFromDays(): void
    {
        // MODIF 2026-05-11 : typage explicite
        // ?DateTimeImmutable + assertNotNull pour narrow le type apres execute()
        // et garantir un échec clair si le callback n'est jamais invoque.
        /** @var \DateTimeImmutable|null $captured */
        $captured = null;
        $this->repo->expects($this->once())
            ->method('purgeTerminatedBefore')
            ->willReturnCallback(function (\DateTimeImmutable $threshold, bool $dryRun) use (&$captured) {
                $captured = $threshold;
                return 0;
            });

        $this->tester->execute(['--days' => 7]);

        $this->assertNotNull($captured, 'purgeTerminatedBefore() n\'a pas ete appele : threshold non capture.');
        $expectedAround = (new \DateTimeImmutable())->modify('-7 days');
        $diffSec = abs($expectedAround->getTimestamp() - $captured->getTimestamp());
        $this->assertLessThan(5, $diffSec, 'threshold doit être = now - 7 days (tolerance 5 sec)');
    }

    public function testEnforcesMinimumDaysOne(): void
    {
        // MODIF 2026-05-11 : idem testThresholdComputedFromDays.
        /** @var \DateTimeImmutable|null $captured */
        $captured = null;
        $this->repo->method('purgeTerminatedBefore')
            ->willReturnCallback(function (\DateTimeImmutable $threshold) use (&$captured) {
                $captured = $threshold;
                return 0;
            });

        $this->tester->execute(['--days' => 0]); // doit être clampe a 1

        $this->assertNotNull($captured, 'purgeTerminatedBefore() n\'a pas ete appele : threshold non capture.');
        $expectedAround = (new \DateTimeImmutable())->modify('-1 days');
        $diffSec = abs($expectedAround->getTimestamp() - $captured->getTimestamp());
        $this->assertLessThan(5, $diffSec);
    }

    public function testReturnsFailureCodeOnException(): void
    {
        $this->repo->method('purgeTerminatedBefore')
            ->willThrowException(new \RuntimeException('boom'));

        $exit = $this->tester->execute([]);

        $this->assertSame(1, $exit, 'Command::FAILURE = 1');
        $this->assertStringContainsString('boom', $this->tester->getDisplay());
    }

    public function testDisplaysRemainingStats(): void
    {
        $this->repo->method('purgeTerminatedBefore')->willReturn(2);

        $this->tester->execute([]);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Restant queued', $output);
        $this->assertStringContainsString('Restant processing', $output);
        $this->assertStringContainsString('Restant done', $output);
        $this->assertStringContainsString('Restant failed', $output);
    }
}
