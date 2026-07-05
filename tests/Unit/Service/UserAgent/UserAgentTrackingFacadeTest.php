<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 */

namespace App\Tests\Unit\Service\UserAgent;

use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Service\UserAgent\UserAgentTrackerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * [Description UserAgentTrackingFacadeTest]
 * MODIF 2026-06-10 : couverture track() — 3 scénarios.
 */
class UserAgentTrackingFacadeTest extends TestCase
{
    /* logger est toujours un mock car chaque test pose une expectation dessus */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testTrackDelegatesToTrackerAndLogsNothingOnSuccess(): void
    {
        $tracker = $this->createMock(UserAgentTrackerService::class);
        $tracker->expects($this->once())
            ->method('track')
            ->with('LOGIN_PAGE_VIEW')
            ->willReturn(['code' => 200, 'erreur' => null]);

        $this->logger->expects($this->never())->method('warning');

        (new UserAgentTrackingFacade($tracker, $this->logger))->track('LOGIN_PAGE_VIEW');
    }

    public function testTrackLogsWarningWhenTrackerFails(): void
    {
        $tracker = $this->createStub(UserAgentTrackerService::class);
        $tracker->method('track')->willReturn(['code' => 500, 'erreur' => 'Pas de requête active']);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[UserAgentTracking] Échec tracking',
                $this->callback(fn(array $ctx) =>
                    $ctx['event_type'] === 'LOGOUT' &&
                    $ctx['erreur'] === 'Pas de requête active'
                )
            );

        (new UserAgentTrackingFacade($tracker, $this->logger))->track('LOGOUT');
    }

    public function testTrackLogsWarningWithNullErreurWhenKeyAbsent(): void
    {
        $tracker = $this->createStub(UserAgentTrackerService::class);
        $tracker->method('track')->willReturn(['code' => 503]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[UserAgentTracking] Échec tracking',
                $this->callback(fn(array $ctx) =>
                    $ctx['event_type'] === 'LOGIN_SUCCESS_REDIRECT' &&
                    $ctx['erreur'] === null
                )
            );

        (new UserAgentTrackingFacade($tracker, $this->logger))->track('LOGIN_SUCCESS_REDIRECT');
    }
}
