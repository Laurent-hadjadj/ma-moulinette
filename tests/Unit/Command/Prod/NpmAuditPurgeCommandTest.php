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

use App\Command\Prod\NpmAuditPurgeCommand;
use App\Repository\NpmProcessingQueueRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[AllowMockObjectsWithoutExpectations]
class NpmAuditPurgeCommandTest extends TestCase
{
    /** @var NpmProcessingQueueRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $repo;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->repo   = $this->createMock(NpmProcessingQueueRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->repo->method('countByStatus')->willReturn([
            'queued' => 0, 'processing' => 0, 'done' => 5, 'failed' => 1,
        ]);

        $cmd = new NpmAuditPurgeCommand($this->repo, $this->logger);
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
        /** @var \DateTimeImmutable|null $captured */
        $captured = null;
        $this->repo->expects($this->once())
            ->method('purgeTerminatedBefore')
            ->willReturnCallback(function (\DateTimeImmutable $threshold, bool $dryRun) use (&$captured) {
                $captured = $threshold;
                return 0;
            });

        $this->tester->execute(['--days' => 7]);

        $this->assertNotNull($captured, 'purgeTerminatedBefore() n\'a pas été appelé : threshold non capturé.');
        $expectedAround = (new \DateTimeImmutable())->modify('-7 days');
        $diffSec = abs($expectedAround->getTimestamp() - $captured->getTimestamp());
        $this->assertLessThan(5, $diffSec, 'threshold doit être = now - 7 days (tolérance 5 sec)');
    }

    public function testEnforcesMinimumDaysOne(): void
    {
        /** @var \DateTimeImmutable|null $captured */
        $captured = null;
        $this->repo->method('purgeTerminatedBefore')
            ->willReturnCallback(function (\DateTimeImmutable $threshold) use (&$captured) {
                $captured = $threshold;
                return 0;
            });

        $this->tester->execute(['--days' => 0]);

        $this->assertNotNull($captured, 'purgeTerminatedBefore() n\'a pas été appelé : threshold non capturé.');
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
