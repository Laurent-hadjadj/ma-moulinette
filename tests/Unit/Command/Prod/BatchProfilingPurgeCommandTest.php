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

use App\Command\Prod\BatchProfilingPurgeCommand;
use App\Repository\BatchProfilingRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * [Description BatchProfilingPurgeCommandTest]
 */
#[AllowMockObjectsWithoutExpectations]
class BatchProfilingPurgeCommandTest extends TestCase
{
    /** @var BatchProfilingRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $repo;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->repo   = $this->createMock(BatchProfilingRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $cmd = new BatchProfilingPurgeCommand($this->repo, $this->logger);
        $this->tester = new CommandTester($cmd);
    }

    public function testDryRunCallsCountOlderThanNotPurge(): void
    {
        $this->repo->expects($this->once())
            ->method('countOlderThan')
            ->with(90)
            ->willReturn(3);
        $this->repo->expects($this->never())->method('purge');

        $exit = $this->tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exit);
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('3 ligne', $output);
        $this->assertStringContainsString('seraient', $output);
    }

    public function testRealRunCallsPurgeNotCountOlderThan(): void
    {
        $this->repo->expects($this->once())
            ->method('purge')
            ->with(90)
            ->willReturn(7);
        $this->repo->expects($this->never())->method('countOlderThan');

        $exit = $this->tester->execute([]);

        $this->assertSame(0, $exit);
        $output = $this->tester->getDisplay();
        $this->assertStringNotContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('7 ligne', $output);
        $this->assertStringContainsString('supprimée', $output);
    }

    public function testDefaultRetentionIsNinetyDays(): void
    {
        $this->repo->expects($this->once())
            ->method('purge')
            ->with(90)
            ->willReturn(0);

        $this->tester->execute([]);
    }

    public function testDaysOptionIsForwardedToRepository(): void
    {
        $this->repo->expects($this->once())
            ->method('purge')
            ->with(30)
            ->willReturn(0);

        $this->tester->execute(['--days' => 30]);
    }

    public function testEnforcesMinimumDaysOne(): void
    {
        $this->repo->expects($this->once())
            ->method('purge')
            ->with(1)
            ->willReturn(0);

        $this->tester->execute(['--days' => 0]); // doit être clampé à 1
    }

    public function testReturnsFailureCodeOnException(): void
    {
        $this->repo->method('purge')
            ->willThrowException(new \RuntimeException('boom'));

        $exit = $this->tester->execute([]);

        $this->assertSame(1, $exit, 'Command::FAILURE = 1');
        $this->assertStringContainsString('boom', $this->tester->getDisplay());
    }

    public function testReturnsFailureCodeOnExceptionInDryRun(): void
    {
        $this->repo->method('countOlderThan')
            ->willThrowException(new \RuntimeException('boom'));

        $exit = $this->tester->execute(['--dry-run' => true]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('boom', $this->tester->getDisplay());
    }
}
