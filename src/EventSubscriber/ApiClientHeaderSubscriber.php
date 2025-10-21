<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * [Description ApiClientHeaderSubscriber]
 */
class ApiClientHeaderSubscriber implements EventSubscriberInterface
{
    private string $appClientToken;
    private array $allowedOrigins;
    private string $internalHeaderName;
    private string $internalHeaderValue;

    public function __construct(
        string $appClientToken,
        array $allowedOrigins = [],
        string $internalHeaderName = 'X-Internal-Front',
        string $internalHeaderValue = 'front-app'
    ) {
        $this->appClientToken = $appClientToken;
        $this->allowedOrigins = $allowedOrigins;
        $this->internalHeaderName = $internalHeaderName;
        $this->internalHeaderValue = $internalHeaderValue;
    }

    /**
     * [Description for getSubscribedEvents]
     *
     * @return array
     *
     * Created at: 21/10/2025 09:30:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onKernelRequest', 20],
        ];
    }

    /**
     * [Description for onKernelRequest]
     *
     * @param RequestEvent $event
     *
     * @return void
     *
     * Created at: 20/10/2025 22:45:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // ✅ On ne s'applique qu'aux routes /api/
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $origin  = $request->headers->get('Origin') ?? '';
        $referer = $request->headers->get('Referer') ?? '';
        $internalHeader = $request->headers->get($this->internalHeaderName);

        $isAllowed =
            $this->isAllowedOrigin($origin, $request) ||
            $this->isAllowedOrigin($referer, $request) ||
            $internalHeader === $this->internalHeaderValue;

        if ($isAllowed) {
            // si le header X-App-Client est vide, on l’ajoute
            if (empty($request->headers->get('X-App-Client'))) {
                $request->headers->set('X-App-Client', $this->appClientToken);
            }
        } else {
            // 🚫 Requête externe : on bloque immédiatement
            $event->setResponse(new JsonResponse([
                'code'    => 403,
                'message' => '[API-Credential] 🚫 Accès interdit : client non autorisé.'
            ], JsonResponse::HTTP_FORBIDDEN));

            // Optionnel : log
            file_put_contents('/tmp/api_denied.log', sprintf(
                "[%s] Tentative externe : Origin=%s | Referer=%s | IP=%s\n",
                date('Y-m-d H:i:s'),
                $origin,
                $referer,
                $request->getClientIp()
            ), FILE_APPEND);
        }
    }

    /**
     * [Description for isAllowedOrigin]
     *
     * @param string $url
     * @param Request|null $request
     *
     * @return bool
     *
     * Created at: 20/10/2025 22:46:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function isAllowedOrigin(string $url, ?Request $request = null): bool
    {
        // Si l'URL contient un des allowed origins → OK
        foreach ($this->allowedOrigins as $allowed) {
            if (str_contains($url, $allowed)) {
                return true;
            }
        }

        // ✅ Fallback : si pas d'Origin/Referer, on autorise si le host Symfony correspond à un allowed origin
        if ($request) {
            $host = $request->getHost();
            foreach ($this->allowedOrigins as $allowed) {
                if (str_contains($host, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }
}
