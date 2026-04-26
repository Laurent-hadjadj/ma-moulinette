<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\UserAgentAnalysisRepository;
use App\Repository\UserAgentEventRepository;
use App\Service\UserAgentAnalysisService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class UserAgentAnalysisServiceTest extends TestCase
{
    private const CHROME_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /** @var UserAgentEventRepository&MockObject */
    private MockObject $eventRepo;

    /** @var UserAgentAnalysisRepository&MockObject */
    private MockObject $analysisRepo;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private UserAgentAnalysisService $service;

    protected function setUp(): void
    {
        $this->eventRepo = $this->createMock(UserAgentEventRepository::class);
        $this->analysisRepo = $this->createMock(UserAgentAnalysisRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new UserAgentAnalysisService(
            $this->eventRepo,
            $this->analysisRepo,
            $this->logger
        );
    }

    public function testRunBatchReturnsErrorWhenSelectPendingEventsFails(): void
    {
        $this->eventRepo->expects($this->once())
            ->method('selectPendingEvents')
            ->with(50)
            ->willReturn(['code' => 500, 'erreur' => 'DB down']);

        $this->eventRepo->expects($this->never())->method('updateProcessingStatus');
        $this->analysisRepo->expects($this->never())->method('insertUserAgentAnalysis');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[Run-Batch] ❌ Échec de la requête selectPendingEvents().',
                $this->callback(fn (array $ctx) => $ctx['code'] === 500 && $ctx['erreur'] === 'DB down')
            );

        $this->assertSame(
            ['code' => 500, 'erreur' => 'DB down'],
            $this->service->runBatch()
        );
    }

    public function testRunBatchReturnsZeroProcessedWhenNoPendingEvents(): void
    {
        $this->eventRepo->expects($this->once())
            ->method('selectPendingEvents')
            ->with(25)
            ->willReturn(['code' => 200, 'liste' => []]);

        $this->eventRepo->expects($this->never())->method('updateProcessingStatus');
        $this->analysisRepo->expects($this->never())->method('insertUserAgentAnalysis');

        $this->assertSame(
            ['code' => 200, 'processed' => 0, 'erreurs' => []],
            $this->service->runBatch(25)
        );
    }

    public function testRunBatchProcessesEventHappyPath(): void
    {
        $event = [
            'id' => 7,
            'user_agent' => self::CHROME_UA,
            'event_type' => 'LOGIN_PAGE_VIEW',
            'url' => '/login',
            'session_id' => 'sess-1',
            'visitor_id' => 'vis-1',
            'user_id' => 42,
        ];

        $this->eventRepo->expects($this->once())
            ->method('selectPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [$event]]);

        $statusCalls = [];
        $this->eventRepo->expects($this->exactly(2))
            ->method('updateProcessingStatus')
            ->willReturnCallback(function (int $id, string $status) use (&$statusCalls) {
                $statusCalls[] = [$id, $status];
                return ['code' => 200];
            });

        $this->analysisRepo->expects($this->once())
            ->method('insertUserAgentAnalysis')
            ->with($this->callback(function (array $map) use ($event) {
                return $map['event_type'] === $event['event_type']
                    && $map['url'] === $event['url']
                    && $map['session_id'] === $event['session_id']
                    && $map['visitor_id'] === $event['visitor_id']
                    && $map['user_id'] === $event['user_id']
                    && is_string($map['detector_version'])
                    && $map['detector_version'] !== ''
                    && $map['created_at'] instanceof \DateTimeImmutable
                    && array_key_exists('device_type', $map)
                    && array_key_exists('os_name', $map)
                    && array_key_exists('browser_name', $map)
                    && is_bool($map['is_bot'])
                    && $map['browser_name'] === 'Chrome';
            }))
            ->willReturn(['code' => 200]);

        $this->logger->expects($this->never())->method('error');

        $result = $this->service->runBatch();

        $this->assertSame(200, $result['code']);
        $this->assertSame(1, $result['processed']);
        $this->assertSame([], $result['erreurs']);
        $this->assertSame([[7, 'PROCESSING'], [7, 'DONE']], $statusCalls);
    }

    public function testRunBatchSkipsEventWhenLockFails(): void
    {
        $event = ['id' => 11, 'user_agent' => self::CHROME_UA, 'event_type' => 't', 'url' => '/u', 'session_id' => 's', 'visitor_id' => 'v', 'user_id' => null];

        $this->eventRepo->expects($this->once())
            ->method('selectPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [$event]]);

        // Un seul appel updateProcessingStatus : celui du lock qui échoue
        $this->eventRepo->expects($this->once())
            ->method('updateProcessingStatus')
            ->with(11, 'PROCESSING')
            ->willReturn(['code' => 409, 'erreur' => 'Already locked']);

        $this->analysisRepo->expects($this->never())->method('insertUserAgentAnalysis');

        $result = $this->service->runBatch();

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['processed']);
        $this->assertSame(
            [['event_id' => 11, 'erreur' => 'Impossible de verrouiller l’événement']],
            $result['erreurs']
        );
    }

    public function testRunBatchMarksEventAsErrorWhenInsertFails(): void
    {
        $event = ['id' => 42, 'user_agent' => self::CHROME_UA, 'event_type' => 't', 'url' => '/u', 'session_id' => 's', 'visitor_id' => 'v', 'user_id' => 5];

        $this->eventRepo->expects($this->once())
            ->method('selectPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [$event]]);

        $statusCalls = [];
        $this->eventRepo->expects($this->exactly(2))
            ->method('updateProcessingStatus')
            ->willReturnCallback(function (int $id, string $status) use (&$statusCalls) {
                $statusCalls[] = [$id, $status];
                return ['code' => 200];
            });

        $this->analysisRepo->expects($this->once())
            ->method('insertUserAgentAnalysis')
            ->willReturn(['code' => 500, 'erreur' => 'Insert failed']);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[Run-Batch] ❌ Échec de la requête insertUserAgentAnalysis().',
                $this->callback(fn (array $ctx) => $ctx['code'] === 500)
            );

        $result = $this->service->runBatch();

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['processed']);
        $this->assertCount(1, $result['erreurs']);
        $this->assertSame(42, $result['erreurs'][0]['event_id']);
        $this->assertStringContainsString('insertUserAgentAnalysis', $result['erreurs'][0]['erreur']);
        // Séquence de status : d'abord PROCESSING (lock), puis ERROR (catch)
        $this->assertSame([[42, 'PROCESSING'], [42, 'ERROR']], $statusCalls);
    }

    public function testRunBatchIsolatesFailuresAndContinuesProcessing(): void
    {
        $eventOk = ['id' => 1, 'user_agent' => self::CHROME_UA, 'event_type' => 't', 'url' => '/u', 'session_id' => 's1', 'visitor_id' => 'v1', 'user_id' => 1];
        $eventKo = ['id' => 2, 'user_agent' => self::CHROME_UA, 'event_type' => 't', 'url' => '/u', 'session_id' => 's2', 'visitor_id' => 'v2', 'user_id' => 2];

        $this->eventRepo->expects($this->once())
            ->method('selectPendingEvents')
            ->willReturn(['code' => 200, 'liste' => [$eventOk, $eventKo]]);

        // Le premier événement passe (PROCESSING + DONE), le second ne prend pas le lock
        $callCount = 0;
        $this->eventRepo->expects($this->exactly(3))
            ->method('updateProcessingStatus')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                // Appel 3 = lock sur event 2 → échec
                return $callCount === 3
                    ? ['code' => 409, 'erreur' => 'locked']
                    : ['code' => 200];
            });

        $this->analysisRepo->expects($this->once()) // uniquement pour l'event 1
            ->method('insertUserAgentAnalysis')
            ->willReturn(['code' => 200]);

        $result = $this->service->runBatch();

        $this->assertSame(1, $result['processed']);
        $this->assertCount(1, $result['erreurs']);
        $this->assertSame(2, $result['erreurs'][0]['event_id']);
    }
}
