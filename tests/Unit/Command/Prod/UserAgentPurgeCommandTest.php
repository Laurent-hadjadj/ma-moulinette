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

use App\Command\Prod\UserAgentPurgeCommand;
use App\Repository\{UserAgentAnalysisRepository, UserAgentEventRepository};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * [Description UserAgentPurgeCommandTest]
 */
#[AllowMockObjectsWithoutExpectations]
class UserAgentPurgeCommandTest extends TestCase
{
    /** @var UserAgentEventRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $eventRepo;
    /** @var UserAgentAnalysisRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $analysisRepo;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->eventRepo    = $this->createMock(UserAgentEventRepository::class);
        $this->analysisRepo = $this->createMock(UserAgentAnalysisRepository::class);
        $this->logger       = $this->createMock(LoggerInterface::class);

        $cmd = new UserAgentPurgeCommand($this->eventRepo, $this->analysisRepo, 180, $this->logger);
        $this->tester = new CommandTester($cmd);
    }

    public function testDryRunCallsCountOlderThanOnBothRepositories(): void
    {
        $this->eventRepo->expects($this->once())->method('countOlderThan')->with(180)->willReturn(3);
        $this->analysisRepo->expects($this->once())->method('countOlderThan')->with(180)->willReturn(2);
        $this->eventRepo->expects($this->never())->method('purge');
        $this->analysisRepo->expects($this->never())->method('purge');

        $exit = $this->tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exit);
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('5 ligne', $output); // 3 + 2
        $this->assertStringContainsString('seraient', $output);
    }

    public function testRealRunCallsPurgeOnBothRepositories(): void
    {
        $this->eventRepo->expects($this->once())->method('purge')->with(180)->willReturn(7);
        $this->analysisRepo->expects($this->once())->method('purge')->with(180)->willReturn(4);
        $this->eventRepo->expects($this->never())->method('countOlderThan');
        $this->analysisRepo->expects($this->never())->method('countOlderThan');

        $exit = $this->tester->execute([]);

        $this->assertSame(0, $exit);
        $output = $this->tester->getDisplay();
        $this->assertStringNotContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('11 ligne', $output); // 7 + 4
        $this->assertStringContainsString('supprimée', $output);
    }

    public function testDisplaysDetailPerTable(): void
    {
        $this->eventRepo->method('purge')->willReturn(7);
        $this->analysisRepo->method('purge')->willReturn(4);

        $this->tester->execute([]);

        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('user_agent_analysis', $output);
    }

    public function testDaysOptionIsForwardedToBothRepositories(): void
    {
        $this->eventRepo->expects($this->once())->method('purge')->with(30)->willReturn(0);
        $this->analysisRepo->expects($this->once())->method('purge')->with(30)->willReturn(0);

        $this->tester->execute(['--days' => 30]);
    }

    public function testEnforcesMinimumDaysOne(): void
    {
        $this->eventRepo->expects($this->once())->method('purge')->with(1)->willReturn(0);
        $this->analysisRepo->expects($this->once())->method('purge')->with(1)->willReturn(0);

        $this->tester->execute(['--days' => 0]); // doit être clampé à 1
    }

    public function testReturnsFailureCodeOnException(): void
    {
        $this->eventRepo->method('purge')
            ->willThrowException(new \RuntimeException('boom'));

        $exit = $this->tester->execute([]);

        $this->assertSame(1, $exit, 'Command::FAILURE = 1');
        $this->assertStringContainsString('boom', $this->tester->getDisplay());
    }

    public function testReturnsFailureCodeOnExceptionInDryRun(): void
    {
        $this->eventRepo->method('countOlderThan')
            ->willThrowException(new \RuntimeException('boom'));

        $exit = $this->tester->execute(['--dry-run' => true]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('boom', $this->tester->getDisplay());
    }
}
