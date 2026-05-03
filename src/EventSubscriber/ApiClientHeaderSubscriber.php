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
use Psr\Log\LoggerInterface;

/**
 * [Description ApiClientHeaderSubscriber]
 */
class ApiClientHeaderSubscriber implements EventSubscriberInterface
{
    private string $appClientToken;
    private array $allowedOrigins;
    private string $internalHeaderName;
    private string $internalHeaderValue;
    private LoggerInterface $logger;
/**
 * @param array<int|string, mixed> $allowedOrigins
 */

    public function __construct(
        string $appClientToken,
        LoggerInterface $logger,
        array $allowedOrigins = [],
        string $internalHeaderName = '',
        string $internalHeaderValue = 'front-app'
    ) {
        $this->appClientToken = $appClientToken;
        $this->allowedOrigins = $allowedOrigins;
        $this->internalHeaderName = $internalHeaderName;
        $this->internalHeaderValue = $internalHeaderValue;
        $this->logger = $logger;
    }

    /**
     * [Description for getSubscribedEvents]
     *
     * @return array<int|string, mixed>
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

        // On ne s'applique qu'aux routes /api/secure/
        if (!str_starts_with($request->getPathInfo(), '/api/secure/')) {
            return;
        }

        $origin         = $request->headers->get('Origin') ?? '';
        $referer        = $request->headers->get('Referer') ?? '';
        $internalHeader = $request->headers->get($this->internalHeaderName);

        //🔒 seules les requêtes venant d’un Origin ou Referer autorisé et portant le header interne correct (X-Internal-Front: front-app) peuvent appeler /api/secure/*.
        $isAllowedOriginOrReferer =
            $this->isAllowedOrigin($origin) ||
            $this->isAllowedOrigin($referer);

        $hasInternalHeader =
            $internalHeader === $this->internalHeaderValue;

        $isAllowed = $isAllowedOriginOrReferer && $hasInternalHeader;

        if ($isAllowed) {
            // si le header X-App-Client est vide, on l’ajoute
            if (empty($request->headers->get('X-App-Client'))) {
                $request->headers->set('X-App-Client', $this->appClientToken);
            }
            return;
        }

        // 🚫 Requête externe : on bloque immédiatement
        $event->setResponse(new JsonResponse([
            'code'    => 403,
            'message' => '[API-Credential] 🚫 Accès interdit : client non autorisé.'
        ], JsonResponse::HTTP_FORBIDDEN));

        // journalisation
        $this->logger->warning('[API-Credential] ☠️ Accès interdit', [
            'origin' => $origin,
            'referer' => $referer,
            'header' => $internalHeader,
            'ip' => $request->getClientIp(),
            'path' => $request->getPathInfo()
        ]);
    }

    /**
     * [Description for isAllowedOrigin]
     *
     * Verifie que le host extrait de l'URL (Origin ou Referer) appartient
     * a la liste des origines autorisees.
     *
     * Why: l'ancienne version retombait sur $request->getHost() quand l'URL
     * etait vide, ce qui validait la requete via le host du serveur lui-meme
     * (toujours dans allowedOrigins) et neutralisait le controle Origin/Referer.
     *
     * @param string $url
     *
     * @return bool
     *
     * Created at: 10/11/2025 12:33:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function isAllowedOrigin(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            return false;
        }

            foreach ($this->allowedOrigins as $allowed) {
                if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                    return true;
            }
        }

        return false;
    }

}
