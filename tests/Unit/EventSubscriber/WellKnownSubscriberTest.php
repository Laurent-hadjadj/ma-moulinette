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

use App\EventSubscriber\WellKnownSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * MODIF 2026-05-15 : tests Unit pour WellKnownSubscriber.
 * Couvre getSubscribedEvents + onKernelRequest (match /.well-known/ -> 204 / no match -> passe).
 */
#[AllowMockObjectsWithoutExpectations]
class WellKnownSubscriberTest extends TestCase
{
    private WellKnownSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new WellKnownSubscriber();
    }

    public function testGetSubscribedEventsReturnsKernelRequestMapping(): void
    {
        $events = WellKnownSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertSame(['onKernelRequest', 10], $events[KernelEvents::REQUEST]);
    }

    public function testOnKernelRequestReturnsNoContentForWellKnownPath(): void
    {
        $event = $this->buildEvent('/.well-known/security.txt');

        $this->subscriber->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testOnKernelRequestIgnoresRootPath(): void
    {
        $event = $this->buildEvent('/');

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestIgnoresApiPath(): void
    {
        $event = $this->buildEvent('/api/secure/projet');

        $this->subscriber->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    private function buildEvent(string $path): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create($path);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
