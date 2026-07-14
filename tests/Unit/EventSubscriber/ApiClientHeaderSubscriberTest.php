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

use App\EventSubscriber\ApiClientHeaderSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse,Request};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * MODIF 2026-05-15 : tests Unit pour ApiClientHeaderSubscriber.
 * Couvre getSubscribedEvents + onKernelRequest (toutes les branches) + isAllowedOrigin
 * (testé indirectement via les chemins d'origin valide/invalide/sous-domaine).
 */
#[AllowMockObjectsWithoutExpectations]
class ApiClientHeaderSubscriberTest extends TestCase
{
    /** @var LoggerInterface&MockObject */ private MockObject $logger;

    private const APP_CLIENT_TOKEN     = 'app-secret-token';
    private const INTERNAL_HEADER      = 'X-Internal-Front';
    private const INTERNAL_VALUE       = 'front-app';
    private const ALLOWED_ORIGINS      = ['example.com', 'localhost'];

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testGetSubscribedEventsReturnsRequestEventMappingWithPriority20(): void
    {
        $events = ApiClientHeaderSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(RequestEvent::class, $events);
        $this->assertSame(['onKernelRequest', 20], $events[RequestEvent::class]);
    }

    public function testOnKernelRequestSkipsWhenPathIsNotApiSecure(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/login');

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestSkipsWhenPathStartsWithDependencyCheckPrefix(): void
    {
        /* Les routes DC ont leur propre token-based subscriber. */
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/dependency-check/upload');

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestAddsXAppClientHeaderWhenOriginAndInternalHeaderValid(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Origin' => 'https://example.com',
            self::INTERNAL_HEADER => self::INTERNAL_VALUE,
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertSame(
            self::APP_CLIENT_TOKEN,
            $event->getRequest()->headers->get('X-App-Client')
        );
    }

    public function testOnKernelRequestPreservesExistingXAppClientHeader(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Origin' => 'https://example.com',
            self::INTERNAL_HEADER => self::INTERNAL_VALUE,
            'X-App-Client' => 'pre-existing-token',
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
        $this->assertSame('pre-existing-token', $event->getRequest()->headers->get('X-App-Client'));
    }

    public function testOnKernelRequestAcceptsRefererWhenOriginAbsent(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Referer' => 'https://example.com/page',
            self::INTERNAL_HEADER => self::INTERNAL_VALUE,
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestAcceptsSubdomainOfAllowedOrigin(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Origin' => 'https://app.example.com',
            self::INTERNAL_HEADER => self::INTERNAL_VALUE,
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestReturns403WhenOriginNotAllowed(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Origin' => 'https://evil.com',
            self::INTERNAL_HEADER => self::INTERNAL_VALUE,
        ]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Accès interdit'));

        $subscriber->onKernelRequest($event);

        $this->assertForbidden($event);
    }

    public function testOnKernelRequestReturns403WhenInternalHeaderMissing(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Origin' => 'https://example.com',
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertForbidden($event);
    }

    public function testOnKernelRequestReturns403WhenInternalHeaderHasWrongValue(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Origin' => 'https://example.com',
            self::INTERNAL_HEADER => 'wrong-value',
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertForbidden($event);
    }

    public function testOnKernelRequestReturns403WhenNoOriginAndNoReferer(): void
    {
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            self::INTERNAL_HEADER => self::INTERNAL_VALUE,
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertForbidden($event);
    }

    public function testOnKernelRequestReturns403WhenOriginUrlIsMalformed(): void
    {
        /* parse_url retourne false ou pas de host pour URL invalide → rejet. */
        $subscriber = $this->buildSubscriber();
        $event = $this->buildEvent('/api/secure/projet', [
            'Origin' => 'not-a-url',
            self::INTERNAL_HEADER => self::INTERNAL_VALUE,
        ]);

        $subscriber->onKernelRequest($event);

        $this->assertForbidden($event);
    }

    private function buildSubscriber(): ApiClientHeaderSubscriber
    {
        return new ApiClientHeaderSubscriber(
            self::APP_CLIENT_TOKEN,
            $this->logger,
            self::ALLOWED_ORIGINS,
            self::INTERNAL_HEADER,
            self::INTERNAL_VALUE
        );
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

    private function assertForbidden(RequestEvent $event): void
    {
        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(JsonResponse::HTTP_FORBIDDEN, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(403, $payload['code']);
    }
}
