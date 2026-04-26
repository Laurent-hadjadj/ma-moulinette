<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\UserAgentTrackerService;
use App\Service\UserAgentTrackingFacade;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserAgentTrackingFacadeTest extends TestCase
{
    /** @var UserAgentTrackerService&MockObject */
    private MockObject $tracker;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private UserAgentTrackingFacade $facade;

    protected function setUp(): void
    {
        $this->tracker = $this->createMock(UserAgentTrackerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->facade = new UserAgentTrackingFacade($this->tracker, $this->logger);
    }

    public function testTrackDelegatesToTrackerAndDoesNotLogOnSuccess(): void
    {
        $this->tracker->expects($this->once())
            ->method('track')
            ->with('page_view')
            ->willReturn(['code' => 200]);

        $this->logger->expects($this->never())->method('warning');

        $this->facade->track('page_view');
    }

    public function testTrackLogsWarningWhenTrackerReturnsFailureCode(): void
    {
        $this->tracker->expects($this->once())
            ->method('track')
            ->with('login_failed')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[UserAgentTracking] Échec tracking',
                ['event_type' => 'login_failed', 'erreur' => 'db down']
            );

        $this->facade->track('login_failed');
    }

    public function testTrackLogsWarningWithNullErreurWhenKeyIsMissing(): void
    {
        $this->tracker->expects($this->once())
            ->method('track')
            ->willReturn(['code' => 418]); // code != 200, pas de clé 'erreur'

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[UserAgentTracking] Échec tracking',
                ['event_type' => 'teapot', 'erreur' => null]
            );

        $this->facade->track('teapot');
    }
}
