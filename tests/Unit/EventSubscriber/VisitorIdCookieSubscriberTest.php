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

use App\EventSubscriber\VisitorIdCookieSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * MODIF 2026-10-16 : tests Unit pour VisitorIdCookieSubscriber.
 * UserAgentTrackerService::track() pose l'attribut de requête `visitor_id`
 * uniquement quand il vient d'en générer un nouveau (cookie absent côté
 * client) — ce subscriber doit alors, et alors seulement, écrire le
 * Set-Cookie correspondant sur la réponse sortante.
 */
#[AllowMockObjectsWithoutExpectations]
class VisitorIdCookieSubscriberTest extends TestCase
{
    private VisitorIdCookieSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new VisitorIdCookieSubscriber();
    }

    public function testGetSubscribedEventsReturnsKernelResponseMapping(): void
    {
        $events = VisitorIdCookieSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame('onKernelResponse', $events[KernelEvents::RESPONSE]);
    }

    public function testOnKernelResponseSetsCookieWhenAttributeIsPresent(): void
    {
        $event = $this->buildEvent(['visitor_id' => '01JA000000000000000000VISIT']);

        $this->subscriber->onKernelResponse($event);

        $cookie = $event->getResponse()->headers->getCookies()[0] ?? null;
        $this->assertNotNull($cookie, 'Le cookie visitor_id doit être posé sur la réponse.');
        $this->assertSame('visitor_id', $cookie->getName());
        $this->assertSame('01JA000000000000000000VISIT', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertGreaterThan(time() + 60 * 60 * 24 * 300, $cookie->getExpiresTime());
    }

    public function testOnKernelResponseDoesNothingWhenAttributeIsAbsent(): void
    {
        $event = $this->buildEvent([]);

        $this->subscriber->onKernelResponse($event);

        $this->assertCount(0, $event->getResponse()->headers->getCookies());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function buildEvent(array $attributes): ResponseEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        foreach ($attributes as $name => $value) {
            $request->attributes->set($name, $value);
        }

        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new Response());
    }
}
