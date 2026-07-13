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
 * MODIF 2026-05-08 : authentification bearer pour les endpoints
 * /api/secure/dependency-check/*.
 *
 * Pourquoi un subscriber dédié plutôt que le firewall security :
 *  - flux machine-to-machine (CI GitLab) sans session
 *  - token modifiable sans toucher aux providers/users Doctrine
 *  - lecture par variable d'environnement (rotation par redéploiement)
 *
 * Garde-fou production : si le token est vide ou égal a la sentinelle "changeme",
 * l'endpoint répond 503 Service Unavailable. Cela evite qu'un oubli de
 * configuration laisse l'endpoint ouvert sans auth.
 *
 * Sécurité : hash_equals() pour comparer en temps constant (anti timing attack).
 */
class DependencyCheckTokenSubscriber implements EventSubscriberInterface
{
    private const HEADER_NAME  = 'X-DependencyCheck-Token';
    private const PATH_PREFIX  = '/api/secure/dependency-check/';
    private const SENTINEL_DEV = 'changeme';

    public function __construct(
        private readonly string $expectedToken,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * [Description for getSubscribedEvents]
     *
     * @return array
     *
     * Created at: 13/07/2026 07:40:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getSubscribedEvents(): array
    {
        // Priorité 25 : doit passer AVANT ApiClientHeaderSubscriber (priorité 20)
        // pour que les routes dependency-check ne soient pas évaluées deux fois.
        return [
            RequestEvent::class => ['onKernelRequest', 25],
        ];
    }

    /**
     * [Description for onKernelRequest]
     *
     * @param RequestEvent $event
     *
     * @return void
     *
     * Created at: 13/07/2026 07:40:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ne s'applique qu'aux routes dependency-check
        if (!str_starts_with($request->getPathInfo(), self::PATH_PREFIX)) {
            return;
        }

        // Garde-fou : token non configure -> endpoint désactivé
        if ($this->expectedToken === '' || $this->expectedToken === self::SENTINEL_DEV) {
            $this->logger->critical(
                '[DC-Ingest] 🔴 DC_INGEST_TOKEN absent ou non configure (changeme), endpoint désactivé.'
            );
            $event->setResponse(new JsonResponse(
                [
                    'code'    => 503,
                    'type'    => 'critical',
                    'message' => 'Endpoint OWASP DependencyCheck désactivé (token non configure cote serveur).',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            ));
            return;
        }

        // Lecture du token dans le header
        $provided = $request->headers->get(self::HEADER_NAME);

        if ($provided === null || $provided === '') {
            $this->logger->warning('[DC-Ingest] Token absent dans la requête.', [
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

        // Comparaison en temps constant (anti timing attack)
        if (!hash_equals($this->expectedToken, $provided)) {
            $this->logger->warning('[DC-Ingest] ⚠️ Token invalide rejeté.', [
                'ip'           => $request->getClientIp(),
                'path'         => $request->getPathInfo(),
                'token_prefix' => substr($provided, 0, 4) . '***', // 4 premiers chars pour audit, jamais le token complet
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

        // Authentifie : on laisse passer le request a la suite de la chaîne
        $this->logger->info('[DC-Ingest] ✅ Token valide.', [
            'ip'   => $request->getClientIp(),
            'path' => $request->getPathInfo(),
        ]);
    }
}
