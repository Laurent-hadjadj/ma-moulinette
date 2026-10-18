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

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * MODIF 2026-10-13 : authentification bearer pour les endpoints
 * /api/secure/zap/*, sur le gabarit DependencyCheckTokenSubscriber.
 *
 * Pourquoi un subscriber dédié plutôt que le firewall security :
 *  - flux machine-to-machine (CI) sans session
 *  - token modifiable sans toucher aux providers/users Doctrine
 *  - lecture par variable d'environnement (rotation par redéploiement)
 *
 * Garde-fou production : si le token est vide ou égal a la sentinelle "changeme",
 * l'endpoint répond 503 Service Unavailable.
 *
 * Sécurité : hash_equals() pour comparer en temps constant (anti timing attack).
 */
class ZapIngestTokenSubscriber implements EventSubscriberInterface
{
    private const HEADER_NAME  = 'X-Zap-Token';
    private const PATH_PREFIX  = '/api/secure/zap/';
    private const SENTINEL_DEV = 'changeme';

    public function __construct(
        private readonly string $expectedToken,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * [Description for getSubscribedEvents]
     *
     * @return array<class-string, array{0: string, 1: int}>
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getSubscribedEvents(): array
    {
        // Priorité 24 : après DependencyCheckTokenSubscriber (25, routes
        // distinctes de toute façon), avant ApiClientHeaderSubscriber (20)
        // pour que les routes zap ne soient pas évaluées deux fois.
        return [
            RequestEvent::class => ['onKernelRequest', 24],
        ];
    }

    /**
     * [Description for onKernelRequest]
     *
     * @param RequestEvent $event
     *
     * @return void
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), self::PATH_PREFIX)) {
            return;
        }

        if ($this->expectedToken === '' || $this->expectedToken === self::SENTINEL_DEV) {
            $this->logger->critical(
                '[ZAP-Ingest] 🔴 ZAP_INGEST_TOKEN absent ou non configure (changeme), endpoint désactivé.'
            );
            $event->setResponse(new JsonResponse(
                [
                    'code'    => 503,
                    'type'    => 'critical',
                    'message' => 'Endpoint ZAP désactivé (token non configure cote serveur).',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            ));
            return;
        }

        $provided = $request->headers->get(self::HEADER_NAME);

        if ($provided === null || $provided === '') {
            $this->logger->warning('[ZAP-Ingest] Token absent dans la requête.', [
                'ip'   => $request->getClientIp(),
                'path' => $request->getPathInfo(),
            ]);
            $event->setResponse(new JsonResponse(
                [
                    'code'    => 401,
                    'type'    => 'error',
                    'message' => sprintf('Header "%s" requis.', self::HEADER_NAME),
                ],
                Response::HTTP_UNAUTHORIZED
            ));
            return;
        }

        if (!hash_equals($this->expectedToken, $provided)) {
            $this->logger->warning('[ZAP-Ingest] ⚠️ Token invalide rejeté.', [
                'ip'           => $request->getClientIp(),
                'path'         => $request->getPathInfo(),
                'token_prefix' => substr($provided, 0, 4) . '***',
            ]);
            $event->setResponse(new JsonResponse(
                [
                    'code'    => 401,
                    'type'    => 'error',
                    'message' => 'Token invalide.',
                ],
                Response::HTTP_UNAUTHORIZED
            ));
            return;
        }

        $this->logger->info('[ZAP-Ingest] ✅ Token valide.', [
            'ip'   => $request->getClientIp(),
            'path' => $request->getPathInfo(),
        ]);
    }
}
