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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;


/**
 * [Description DemoModeSubscriber]
 * Applique le mode démo (`APP_MODE_DEMO=1`) : toute requête absente de la liste blanche {@see ModeDemo} est refusée,
 * AVANT le routage et le pare-feu de sécurité (priorité haute), donc même pour une route inconnue ou protégée par un jeton.
 *
 * Le refus prend trois formes selon l'appelant :
 * - **page HTML** : redirection vers la page « Action indisponible » (`/demo/indisponible`, route autorisée), rendue par
 *   un contrôleur avec le gabarit de l'application (en-tête, pied de page) ;
 * - **appel d'API de l'interface** (en-tête `X-Api-Custom-403`, envoyé par l'interface) : HTTP 200 et corps
 *   `{x-api-custom-403, code: 403, type: warning, message}`, que l'interface affiche dans son bandeau de message, comme
 *   pour un rôle insuffisant (même convention que `ApiSecurityHandler`) ;
 * - **autre appel d'API** : HTTP 403 et corps JSON `{code: 403, type: warning, message}`.
 *
 * Désactivé par défaut (`APP_MODE_DEMO=0`) : sans effet sur la production.
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
final class DemoModeSubscriber implements EventSubscriberInterface
{
    public const MESSAGE = 'Action indisponible dans l\'instance de démonstration.';

    /** Nom de la route de la page de refus (voir `DemoController`). */
    public const ROUTE_INDISPONIBLE = 'demo_indisponible';

    public function __construct(
        private readonly bool $modeDemo,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
    ) {}

    /**
     * [Description for getSubscribedEvents]
     *
     * @return array<string, array{0: string, 1: int}>
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    public static function getSubscribedEvents(): array
    {
        // Avant le routage (32), le pare-feu (8) et les contrôleurs de jetons (25).
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    /**
     * [Description for onKernelRequest]
     *
     * @param RequestEvent $event
     *
     * @return void
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
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

    /**
     * [Description for reponseRefus]
     *
     * @param Request $request
     *
     * @return Response
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function reponseRefus(Request $request): Response
    {
        $attendJson = str_starts_with($request->getPathInfo(), '/api/')
            || $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');

        if ($attendJson) {
            // Convention de l'application (ApiSecurityHandler) : avec l'en-tête X-Api-Custom-403, l'interface attend un
            // HTTP 200 dont le corps porte le code, le type et le message à afficher.
            $reponseInterface = filter_var($request->headers->get('X-Api-Custom-403'), FILTER_VALIDATE_BOOLEAN);

            $corps = ['code' => Response::HTTP_FORBIDDEN, 'type' => 'warning', 'message' => self::MESSAGE];
            if ($reponseInterface) {
                $corps = ['x-api-custom-403' => true] + $corps;
            }

            return new JsonResponse($corps, $reponseInterface ? Response::HTTP_OK : Response::HTTP_FORBIDDEN);
        }

        // Page HTML : le routage n'a pas encore eu lieu, le contexte du générateur d'URL (préfixe, hôte) vient de la requête.
        $this->router->getContext()->fromRequest($request);

        return new RedirectResponse($this->router->generate(self::ROUTE_INDISPONIBLE));
    }
}
