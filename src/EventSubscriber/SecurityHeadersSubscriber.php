<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * [Description SecurityHeadersSubscriber]
 *
 * Pose les en-têtes de sécurité HTTP absents jusqu'ici (CSP, anti-clickjacking,
 * X-Content-Type-Options) et retire la fuite de version PHP (X-Powered-By) —
 * écarts confirmés par un scan ZAP actif complet le 16/10/2026 (voir
 * mkDocs/docs/ma-moulinette/developpement/securite.md). `script-src` est
 * relâché uniquement en dev (`kernel.debug`) car la Web Debug Toolbar Symfony
 * injecte du JS inline sans nonce — elle est déjà absente en prod, donc la
 * politique réellement auditée n'est pas affaiblie.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $debug,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * [Description for onKernelResponse]
     *
     * @param ResponseEvent $event
     *
     * @return void
     *
     * Created at: 21/10/2026 08:53:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Émis par PHP (expose_php) avant que Symfony ne construise sa
        // Response : absent de $response->headers, donc pas retirable via
        // headers->remove(). header_remove() fonctionne tant qu'il est
        // appelé avant l'envoi effectif des en-têtes (le cas ici).
        header_remove('X-Powered-By');

        $headers = $event->getResponse()->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');

        // En production, applique la CSP restrictive.
        // En développement, ne pose pas de CSP depuis ce subscriber.
        if (!$this->debug) {
            $headers->set('Content-Security-Policy', $this->buildCsp());
        }
    }

    private function buildCsp(): string
    {
        // La Web Debug Toolbar (dev uniquement) injecte du JS inline sans nonce.
        //$scriptSrc = $this->debug ? "'self' 'unsafe-inline'" : "'self'";

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            //"script-src {$scriptSrc}",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
