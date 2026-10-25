<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 */

namespace App\Tests\Unit\Service\UserAgent;

use App\Repository\UserAgentAnalysisRepository;
use App\Repository\UserAgentEventRepository;
use App\Service\UserAgent\UserAgentAnalysisService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * [Description UserAgentAnalysisServiceTest]
 * MODIF 25/10/2026 : couverture de runBatch(), y compris le passage de
 * selectPendingEvents() à claimPendingEvents() (verrouillage FOR UPDATE SKIP
 * LOCKED contre le traitement concurrent d'un même événement depuis plusieurs
 * pages — Accueil, Projet, Statistique).
 */
#[AllowMockObjectsWithoutExpectations]
class UserAgentAnalysisServiceTest extends TestCase
{
    /** @var UserAgentEventRepository&MockObject */    private MockObject $eventRepository;
    /** @var UserAgentAnalysisRepository&MockObject */ private MockObject $analysisRepository;
    /** @var LoggerInterface&MockObject */             private MockObject $logger;

    private UserAgentAnalysisService $service;

    protected function setUp(): void
    {
        $this->eventRepository    = $this->createMock(UserAgentEventRepository::class);
        $this->analysisRepository = $this->createMock(UserAgentAnalysisRepository::class);
        $this->logger              = $this->createMock(LoggerInterface::class);

        $this->service = new UserAgentAnalysisService(
            $this->eventRepository,
            $this->analysisRepository,
            $this->logger
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function makePendingEvent(int $id): array
    {
        return [
            'id'          => $id,
            'event_type'  => 'ACCUEIL',
            'url'         => '/accueil',
            'user_agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
            'session_id'  => 'session-abc',
            'visitor_id'  => '550e8400-e29b-41d4-a716-446655440000',
            'user_id'     => null,
        ];
    }

    public function testRunBatchReturnsErrorWhenClaimFails(): void
    {
        $this->eventRepository->expects($this->once())
            ->method('claimPendingEvents')
            ->with(50)
            ->willReturn(['code' => 500, 'erreur' => 'connexion perdue']);

        $this->logger->expects($this->once())->method('error');

        $result = $this->service->runBatch();

        $this->assertSame(500, $result['code']);
        $this->assertSame('connexion perdue', $result['erreur']);
    }

    public function testRunBatchReturnsSuccessWithZeroProcessedWhenNoPendingEvents(): void
    {
        $this->eventRepository->method('claimPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [], 'erreur' => '']);

        $result = $this->service->runBatch(100);

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['processed']);
        $this->assertSame([], $result['erreurs']);
    }

    public function testRunBatchProcessesEventSuccessfully(): void
    {
        $event = $this->makePendingEvent(1);
        $this->eventRepository->method('claimPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [$event], 'erreur' => '']);

        $this->analysisRepository->expects($this->once())
            ->method('insertUserAgentAnalysis')
            ->willReturn(['code' => 200, 'erreur' => '']);

        $this->eventRepository->expects($this->once())
            ->method('updateProcessingStatus')
            ->with(1, 'DONE', $this->isInstanceOf(\DateTimeImmutable::class));

        $result = $this->service->runBatch();

        $this->assertSame(200, $result['code']);
        $this->assertSame(1, $result['processed']);
        $this->assertSame([], $result['erreurs']);
    }

    public function testRunBatchRecordsErrorWhenInsertFails(): void
    {
        $event = $this->makePendingEvent(2);
        $this->eventRepository->method('claimPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [$event], 'erreur' => '']);

        $this->analysisRepository->method('insertUserAgentAnalysis')
            ->willReturn(['code' => 500, 'erreur' => 'échec insertion']);

        $this->eventRepository->expects($this->once())
            ->method('updateProcessingStatus')
            ->with(2, 'ERROR', $this->isInstanceOf(\DateTimeImmutable::class));

        $result = $this->service->runBatch();

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['processed']);
        $this->assertCount(1, $result['erreurs']);
        $this->assertSame(2, $result['erreurs'][0]['event_id']);
    }

    public function testRunBatchContinuesAfterOneEventFails(): void
    {
        $failing    = $this->makePendingEvent(3);
        $succeeding = $this->makePendingEvent(4);

        $this->eventRepository->method('claimPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [$failing, $succeeding], 'erreur' => '']);

        $this->analysisRepository->method('insertUserAgentAnalysis')
            ->willReturnOnConsecutiveCalls(
                ['code' => 500, 'erreur' => 'échec insertion'],
                ['code' => 200, 'erreur' => '']
            );

        $result = $this->service->runBatch();

        $this->assertSame(200, $result['code']);
        $this->assertSame(1, $result['processed']);
        $this->assertCount(1, $result['erreurs']);
        $this->assertSame(3, $result['erreurs'][0]['event_id']);
    }

    public function testRunBatchPassesLimitToClaimPendingEvents(): void
    {
        $this->eventRepository->expects($this->once())
            ->method('claimPendingEvents')
            ->with(25)
            ->willReturn(['code' => 200, 'liste' => [], 'erreur' => '']);

        $this->service->runBatch(25);
    }
}
