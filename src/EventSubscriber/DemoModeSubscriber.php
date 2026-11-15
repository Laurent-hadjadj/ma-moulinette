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

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Security\ModeDemo;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Applique le mode démo (`APP_MODE_DEMO=1`) : toute requête absente de la liste blanche {@see ModeDemo} reçoit un 403,
 * AVANT le routage et le pare-feu de sécurité (priorité haute), donc même pour une route inconnue ou protégée par un jeton.
 *
 * Désactivé par défaut (`APP_MODE_DEMO=0`) : sans effet sur la production.
 */
final class DemoModeSubscriber implements EventSubscriberInterface
{
    public const MESSAGE = 'Action indisponible dans l\'instance de démonstration.';

    public function __construct(
        private readonly bool $modeDemo,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * [Description for getSubscribedEvents]
     *
     * @return array<string, array{0: string, 1: int}>
     *
     * Created at: 15/11/2026 10:39:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public static function getSubscribedEvents(): array
    {
        // Avant le routage (32), le pare-feu (8) et les contrôleurs de jetons (25).
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->modeDemo || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (ModeDemo::autorise($request->getMethod(), $request->getPathInfo())) {
            return;
        }

        $this->logger->warning('[Démo] requête refusée (hors liste blanche)', [
            'methode' => $request->getMethod(),
            'chemin' => $request->getPathInfo(),
            'client_ip' => $request->getClientIp(),
        ]);

        $event->setResponse($this->reponseRefus($request));
    }

    private function reponseRefus(Request $request): Response
    {
        $attendJson = str_starts_with($request->getPathInfo(), '/api/')
            || $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        if ($attendJson) {
            return new JsonResponse(['code' => 403, 'type' => 'demo', 'message' => self::MESSAGE], Response::HTTP_FORBIDDEN);
        }

        return new Response(
            '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>Démonstration</title></head>'
            . '<body><h1>' . self::MESSAGE . '</h1><p><a href="/accueil">Retour à l\'accueil</a></p></body></html>',
            Response::HTTP_FORBIDDEN,
            ['Content-Type' => 'text/html; charset=utf-8']
        );
    }
}
