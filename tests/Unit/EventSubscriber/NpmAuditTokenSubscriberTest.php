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

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\NpmAuditTokenSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Couvre les 6 branches (via ClientTokenVerifier, pas de hash_equals()
 * inline comme DependencyCheckTokenSubscriber/ZapIngestTokenSubscriber) :
 *   - path non match → no-op
 *   - token serveur vide → 503
 *   - token serveur = 'changeme' → 503 (sentinelle dev)
 *   - header X-NpmAudit-Token manquant → 401
 *   - header X-NpmAudit-Token invalide → 401
 *   - token valide → passe (log info)
 */
#[AllowMockObjectsWithoutExpectations]
class NpmAuditTokenSubscriberTest extends TestCase
{
    /** @var LoggerInterface&MockObject */ private MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetSubscribedEventsReturnsRequestEventMappingWithPriority25(): void
    {
        $events = NpmAuditTokenSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(RequestEvent::class, $events);
        $this->assertSame(['onKernelRequest', 25], $events[RequestEvent::class]);
    }

    public function testOnKernelRequestSkipsWhenPathDoesNotMatchPrefix(): void
    {
        $subscriber = new NpmAuditTokenSubscriber('valid-token', $this->logger);
        $event = $this->buildEvent('/api/secure/projet/version');

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestReturns503WhenServerTokenIsEmpty(): void
    {
        $subscriber = new NpmAuditTokenSubscriber('', $this->logger);
        $event = $this->buildEvent('/api/secure/npm-audit/upload');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('NPMAUDIT_INGEST_TOKEN absent'));

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_SERVICE_UNAVAILABLE, 503);
    }

    public function testOnKernelRequestReturns503WhenServerTokenIsSentinelChangeme(): void
    {
        $subscriber = new NpmAuditTokenSubscriber('changeme', $this->logger);
        $event = $this->buildEvent('/api/secure/npm-audit/upload');

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_SERVICE_UNAVAILABLE, 503);
        $payload = json_decode((string) $event->getResponse()->getContent(), true);
        $this->assertSame('critical', $payload['type']);
    }

    public function testOnKernelRequestReturns401WhenHeaderMissing(): void
    {
        $subscriber = new NpmAuditTokenSubscriber('expected', $this->logger);
        $event = $this->buildEvent('/api/secure/npm-audit/upload');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Token absent ou invalide'));

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_UNAUTHORIZED, 401);
        $payload = json_decode((string) $event->getResponse()->getContent(), true);
        $this->assertSame('error', $payload['type']);
        $this->assertStringContainsString('X-NpmAudit-Token', $payload['message']);
    }

    public function testOnKernelRequestReturns401WhenHeaderEmptyString(): void
    {
        $subscriber = new NpmAuditTokenSubscriber('expected', $this->logger);
        $event = $this->buildEvent('/api/secure/npm-audit/upload', ['X-NpmAudit-Token' => '']);

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_UNAUTHORIZED, 401);
    }

    public function testOnKernelRequestReturns401WhenTokenIsInvalid(): void
    {
        $subscriber = new NpmAuditTokenSubscriber('expected-token-XYZ', $this->logger);
        $event = $this->buildEvent('/api/secure/npm-audit/upload', ['X-NpmAudit-Token' => 'wrong-token-ABC']);

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_UNAUTHORIZED, 401);
        $payload = json_decode((string) $event->getResponse()->getContent(), true);
        $this->assertSame('Token invalide.', $payload['message']);
    }

    public function testOnKernelRequestPassesThroughWhenTokenIsValid(): void
    {
        $subscriber = new NpmAuditTokenSubscriber('correct-token', $this->logger);
        $event = $this->buildEvent('/api/secure/npm-audit/upload', ['X-NpmAudit-Token' => 'correct-token']);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Token valide'));

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * @param array<string, string> $headers
     */
    private function buildEvent(string $path, array $headers = []): RequestEvent
    {
        $kernel  = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($path);
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function assertResponseStatus(RequestEvent $event, int $httpStatus, int $payloadCode): void
    {
        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($httpStatus, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame($payloadCode, $payload['code']);
    }
}
