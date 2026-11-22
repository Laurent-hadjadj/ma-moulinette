<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\EventSubscriber;

use App\Security\ClientTokenVerifier;
use App\Security\ResultatJeton;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * MODIF 2026-11-20 : authentification bearer pour les endpoints
 * /api/secure/npm-audit/*, même rôle que DependencyCheckTokenSubscriber /
 * ZapIngestTokenSubscriber (flux machine-to-machine CI, sans session).
 *
 * Contrairement à ces deux-là (comparaison hash_equals() recopiée inline),
 * celui-ci utilise {@see ClientTokenVerifier} — classe factorisée depuis le
 * même besoin, à adopter pour tout nouveau subscriber de ce type plutôt que
 * de dupliquer une 3ᵉ fois la logique inline.
 */
class NpmAuditTokenSubscriber implements EventSubscriberInterface
{
    private const HEADER_NAME = 'X-NpmAudit-Token';
    private const PATH_PREFIX = '/api/secure/npm-audit/';

    public function __construct(
        private readonly string $expectedToken,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<class-string, array{0: string, 1: int}>
     */
    public static function getSubscribedEvents(): array
    {
        // Priorité 25 : doit passer AVANT ApiClientHeaderSubscriber (priorité 20)
        // pour que les routes npm-audit ne soient pas évaluées deux fois.
        return [
            RequestEvent::class => ['onKernelRequest', 25],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), self::PATH_PREFIX)) {
            return;
        }

        $provided = $request->headers->get(self::HEADER_NAME);
        $resultat = ClientTokenVerifier::verifier($this->expectedToken, $provided);

        if ($resultat === ResultatJeton::Desactive) {
            $this->logger->critical(
                '[NpmAudit-Ingest] 🔴 NPMAUDIT_INGEST_TOKEN absent ou non configuré (changeme), endpoint désactivé.'
            );
            $event->setResponse(new JsonResponse(
                [
                    'code'    => 503,
                    'type'    => 'critical',
                    'message' => 'Endpoint npm audit désactivé (token non configuré côté serveur).',
                ],
                Response::HTTP_SERVICE_UNAVAILABLE
            ));
            return;
        }

        if ($resultat === ResultatJeton::Refuse) {
            $this->logger->warning('[NpmAudit-Ingest] ⚠️ Token absent ou invalide rejeté.', [
                'ip'   => $request->getClientIp(),
                'path' => $request->getPathInfo(),
            ]);
            $event->setResponse(new JsonResponse(
                [
                    'code'    => 401,
                    'type'    => 'error',
                    'message' => $provided === null || $provided === ''
                        ? sprintf('Header "%s" requis.', self::HEADER_NAME)
                        : 'Token invalide.',
                ],
                Response::HTTP_UNAUTHORIZED
            ));
            return;
        }

        $this->logger->info('[NpmAudit-Ingest] ✅ Token valide.', [
            'ip'   => $request->getClientIp(),
            'path' => $request->getPathInfo(),
        ]);
    }
}
