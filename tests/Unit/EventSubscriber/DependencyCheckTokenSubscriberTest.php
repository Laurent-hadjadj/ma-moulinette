<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\DependencyCheckTokenSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse,Request,Response};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * MODIF 2026-05-15 : tests Unit pour DependencyCheckTokenSubscriber.
 * Couvre les 6 branches :
 *   - path non match → no-op
 *   - token serveur vide → 503
 *   - token serveur = 'changeme' → 503 (sentinelle dev)
 *   - header X-DependencyCheck-Token manquant → 401
 *   - header X-DependencyCheck-Token invalide → 401
 *   - token valide → passe (log info)
 */
#[AllowMockObjectsWithoutExpectations]
class DependencyCheckTokenSubscriberTest extends TestCase
{
    /** @var LoggerInterface&MockObject */ private MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetSubscribedEventsReturnsRequestEventMappingWithPriority25(): void
    {
        $events = DependencyCheckTokenSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(RequestEvent::class, $events);
        $this->assertSame(['onKernelRequest', 25], $events[RequestEvent::class]);
    }

    public function testOnKernelRequestSkipsWhenPathDoesNotMatchPrefix(): void
    {
        $subscriber = new DependencyCheckTokenSubscriber('valid-token', $this->logger);
        $event = $this->buildEvent('/api/secure/projet/version');

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestReturns503WhenServerTokenIsEmpty(): void
    {
        $subscriber = new DependencyCheckTokenSubscriber('', $this->logger);
        $event = $this->buildEvent('/api/secure/dependency-check/upload');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('DC_INGEST_TOKEN absent'));

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_SERVICE_UNAVAILABLE, 503);
    }

    public function testOnKernelRequestReturns503WhenServerTokenIsSentinelChangeme(): void
    {
        $subscriber = new DependencyCheckTokenSubscriber('changeme', $this->logger);
        $event = $this->buildEvent('/api/secure/dependency-check/upload');

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_SERVICE_UNAVAILABLE, 503);
        $payload = json_decode($event->getResponse()->getContent(), true);
        $this->assertSame('critical', $payload['type']);
    }

    public function testOnKernelRequestReturns401WhenHeaderMissing(): void
    {
        $subscriber = new DependencyCheckTokenSubscriber('expected', $this->logger);
        $event = $this->buildEvent('/api/secure/dependency-check/upload');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Token absent'));

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_UNAUTHORIZED, 401);
        $payload = json_decode($event->getResponse()->getContent(), true);
        $this->assertSame('error', $payload['type']);
        $this->assertStringContainsString('X-DependencyCheck-Token', $payload['message']);
    }

    public function testOnKernelRequestReturns401WhenHeaderEmptyString(): void
    {
        $subscriber = new DependencyCheckTokenSubscriber('expected', $this->logger);
        $event = $this->buildEvent('/api/secure/dependency-check/upload', ['X-DependencyCheck-Token' => '']);

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_UNAUTHORIZED, 401);
    }

    public function testOnKernelRequestReturns401WhenTokenIsInvalid(): void
    {
        $subscriber = new DependencyCheckTokenSubscriber('expected-token-XYZ', $this->logger);
        $event = $this->buildEvent('/api/secure/dependency-check/upload', ['X-DependencyCheck-Token' => 'wrong-token-ABC']);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Token invalide rejeté'), $this->callback(
                /* On vérifie que le token n'est pas loggé en clair (audit prefix + ***). */
                fn ($ctx) => str_contains($ctx['token_prefix'] ?? '', '***')
                    && !str_contains($ctx['token_prefix'] ?? '', 'wrong-token-ABC')
            ));

        $subscriber->onKernelRequest($event);

        $this->assertResponseStatus($event, Response::HTTP_UNAUTHORIZED, 401);
        $payload = json_decode($event->getResponse()->getContent(), true);
        $this->assertSame('Token invalide.', $payload['message']);
    }

    public function testOnKernelRequestPassesThroughWhenTokenIsValid(): void
    {
        $subscriber = new DependencyCheckTokenSubscriber('correct-token', $this->logger);
        $event = $this->buildEvent('/api/secure/dependency-check/upload', ['X-DependencyCheck-Token' => 'correct-token']);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Token valide'));

        $subscriber->onKernelRequest($event);

        /* Aucune réponse posée : la requête continue son chemin */
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
        $payload = json_decode($response->getContent(), true);
        $this->assertSame($payloadCode, $payload['code']);
    }
}
