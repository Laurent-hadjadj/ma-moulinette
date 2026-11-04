<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\SecurityHeadersSubscriber;
use App\Security\CspNonceProvider;
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
        $subscriber = new SecurityHeadersSubscriber(debug: false, nonceProvider: new CspNonceProvider());
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

    public function testOnKernelResponseDoesNotSetCspInDebugMode(): void
    {
        // MODIF 2026-10-21 : la Web Debug Toolbar injecte du JS inline sans
        // nonce ; même un script-src relâché ('unsafe-inline') la bloquait
        // encore dans certains cas, donc on ne pose plus aucun CSP en dev.
        $subscriber = new SecurityHeadersSubscriber(debug: true, nonceProvider: new CspNonceProvider());
        $event = $this->buildEvent();

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertFalse($headers->has('Content-Security-Policy'));
        // Les autres en-têtes restent posés en dev.
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));
    }

    /**
     * MODIF 04/11/2026 : interrupteur temporaire `$cspEnabled` (services.yaml)
     * — une config nginx distincte en recette pose encore sa propre CSP
     * statique en plus de celle-ci, et l'intersection des deux CSP reçues
     * bloquait le JS applicatif. Les autres en-têtes restent posés, seule la
     * CSP (et l'injection de nonce associée) est désactivable sans retirer
     * le code.
     */
    public function testOnKernelResponseDoesNotSetCspWhenDisabledByFlag(): void
    {
        $subscriber = new SecurityHeadersSubscriber(debug: false, nonceProvider: new CspNonceProvider(), cspEnabled: false);
        $event = $this->buildEvent();

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertFalse($headers->has('Content-Security-Policy'));
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));
    }

    public function testOnKernelResponseDoesNothingOnSubRequest(): void
    {
        $subscriber = new SecurityHeadersSubscriber(debug: false, nonceProvider: new CspNonceProvider());
        $event = $this->buildEvent(HttpKernelInterface::SUB_REQUEST);

        $subscriber->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertFalse($headers->has('Content-Security-Policy'));
        $this->assertFalse($headers->has('X-Frame-Options'));
        $this->assertFalse($headers->has('X-Content-Type-Options'));
    }

    /**
     * MODIF 03/11/2026 : `script-src 'self'` seul bloquait en réalité tout le
     * JS applicatif en prod/recette — asset-mapper rend `<script
     * type="importmap">`/`<script type="module">` en inline, jamais couvert
     * par `'self'`. Vérifie que le nonce injecté dans le corps HTML est
     * exactement celui annoncé dans l'en-tête CSP (sinon le navigateur
     * bloque quand même).
     *
     * MODIF 04/11/2026 : étend la vérification à une balise `<script
     * src="...">` classique (ex. `/bundles/easyadmin/app.js`, rendue par du
     * code vendor qu'on ne maîtrise pas) — `'strict-dynamic'` désactive tout
     * allow-listing par origine, y compris `'self'`, donc elle doit recevoir
     * le nonce elle aussi, confirmé en conditions réelles (back-office
     * EasyAdmin cassé malgré un `script-src 'self' ...` en apparence correct).
     * Vérifie aussi qu'une balise portant déjà un nonce n'en reçoit pas un
     * second.
     */
    public function testOnKernelResponseInjectsMatchingNonceOnScriptTagsWithoutOne(): void
    {
        $subscriber = new SecurityHeadersSubscriber(debug: false, nonceProvider: new CspNonceProvider());

        $response = new Response(
            '<html><head><script type="importmap">{"imports":{}}</script>'
            . '<script type="module">import \'login\';</script>'
            . '<script src="/bundles/easyadmin/app.e4c11ab9.js"></script>'
            . '<script nonce="deja-present">console.log(1);</script></head></html>'
        );
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $response);

        $subscriber->onKernelResponse($event);

        $csp = $event->getResponse()->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([a-f0-9]{32})'/", $csp, (string) $csp);
        $matchCount = preg_match("/'nonce-([a-f0-9]{32})'/", $csp, $matches);
        $this->assertSame(1, $matchCount);
        $nonce = $matches[1];

        $content = (string) $event->getResponse()->getContent();
        $this->assertStringContainsString("<script nonce=\"{$nonce}\" type=\"importmap\">", $content);
        $this->assertStringContainsString("<script nonce=\"{$nonce}\" type=\"module\">", $content);
        $this->assertStringContainsString("<script nonce=\"{$nonce}\" src=\"/bundles/easyadmin/app.e4c11ab9.js\"></script>", $content);
        $this->assertStringContainsString('<script nonce="deja-present">', $content);
        $this->assertStringNotContainsString('nonce="deja-present" nonce=', $content);
    }

    public function testOnKernelResponseDoesNotTouchNonHtmlContent(): void
    {
        $subscriber = new SecurityHeadersSubscriber(debug: false, nonceProvider: new CspNonceProvider());

        $response = new Response('{"ok":true}');
        $response->headers->set('Content-Type', 'application/json');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $response);

        $subscriber->onKernelResponse($event);

        $this->assertSame('{"ok":true}', $event->getResponse()->getContent());
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
