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

use App\EventSubscriber\SecurityHeadersSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * MODIF 2026-10-17 : tests Unit pour SecurityHeadersSubscriber.
 * L'appel natif header_remove('X-Powered-By') n'a aucun effet observable sur
 * l'objet Response — non testable de façon fiable en Unit, uniquement en
 * conditions HTTP réelles (voir tests/Integration et la vérification manuelle
 * curl décrite dans le plan).
 */
#[AllowMockObjectsWithoutExpectations]
class SecurityHeadersSubscriberTest extends TestCase
{
    public function testGetSubscribedEventsReturnsKernelResponseMapping(): void
    {
        $events = SecurityHeadersSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame('onKernelResponse', $events[KernelEvents::RESPONSE]);
    }

    public function testOnKernelResponseSetsAllHeadersInProdMode(): void
    {
        $subscriber = new SecurityHeadersSubscriber(debug: false);
        $event = $this->buildEvent();

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));

        $csp = $headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $this->extractDirective($csp, 'script-src'));
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function testOnKernelResponseRelaxesScriptSrcInDebugMode(): void
    {
        $subscriber = new SecurityHeadersSubscriber(debug: true);
        $event = $this->buildEvent();

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function testOnKernelResponseDoesNothingOnSubRequest(): void
    {
        $subscriber = new SecurityHeadersSubscriber(debug: false);
        $event = $this->buildEvent(HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertFalse($headers->has('Content-Security-Policy'));
        $this->assertFalse($headers->has('X-Frame-Options'));
        $this->assertFalse($headers->has('X-Content-Type-Options'));
    }

    private function extractDirective(string $csp, string $directive): string
    {
        foreach (explode('; ', $csp) as $part) {
            if (str_starts_with($part, $directive)) {
                return $part;
            }
        }
        return '';
    }

    private function buildEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, new Request(), $requestType, new Response());
    }
}
