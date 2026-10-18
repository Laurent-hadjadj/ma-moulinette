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

namespace App\Tests\Integration\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * MODIF 2026-10-17 : vérifie les en-têtes de sécurité HTTP sur une vraie
 * réponse.
 *
 * Cible `/` (route `re_login`) en anonyme plutôt que `/login` : Symfony lui
 * répond par une 302 vers `/login` sans jamais appeler de contrôleur, donc
 * sans dépendance DB. `/login` a été écarté après investigation : un bug
 * préexistant et sans rapport (UserAgentEventRepository::insertUserAgentEvent()
 * lève NoActiveTransaction sur rollBack() si beginTransaction() échoue lui-même)
 * y déclenche une 500 — également écarté une page d'erreur/404 "classique" :
 * en debug (`kernel.debug=true`, cas de l'environnement `test`),
 * Symfony\Component\HttpKernel\EventListener\ErrorListener::removeCspHeader()
 * retire volontairement le CSP sur toute page d'exception pour ne pas casser
 * le rendu de la page de debug elle-même — ce n'est pas une régression du
 * subscriber, juste un comportement Symfony à éviter dans ce test précis.
 */
class SecurityHeadersSubscriberTest extends WebTestCase
{
    public function testAnonymousResponseHasSecurityHeaders(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $headers = $client->getResponse()->headers;

        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('DENY', $headers->get('X-Frame-Options'));

        $csp = $headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    /**
     * Best-effort : le SAPI de test (CLI) n'émet pas X-Powered-By, avec ou
     * sans correctif — pas un SAPI web (php-cgi/php-fpm). La vérification
     * qui compte reste le curl manuel contre `symfony serve`, décrite dans
     * le plan.
     */
    public function testAnonymousResponseDoesNotExposePhpPoweredBy(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertFalse($client->getResponse()->headers->has('X-Powered-By'));
    }
}
