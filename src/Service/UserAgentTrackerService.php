<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2025..
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service;

use App\Repository\UserAgentEventRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * [Description UserAgentTrackerService]
 */
class UserAgentTrackerService
{
    public function __construct(
        private RequestStack $requestStack,
        private UserAgentEventRepository $repository,
        private Security $security,
        private string $appSalt
    ) {}

    /**
     * Collecte et enregistre un événement User-Agent.
     *
     * @param string $eventType Type fonctionnel de l'événement (LOGIN_PAGE_VIEW, LOGIN_SUCCESS_REDIRECT, LOGOUT)
     *
     * @return array ['code'=>200,'error'=>null] ou erreur
     */
    public function track(string $eventType): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return ['code' => 500, 'error' => 'Pas de requête active'];
        }

        $sessionId = $request->getSession()->getId();
        $user = $this->security->getUser();
        $userId = $user?->getId();

        $map = [
            'event_type' => $eventType,
            'url' => $request->getPathInfo(),
            'user_agent' => $request->headers->get('User-Agent'),
            'session_id' => $sessionId,
            'user_id' => $userId,
            'auth_state' => $user ? 'AUTHENTICATED' : 'ANONYMOUS',
            'processing_status' => 'PENDING',
            'ip_hash' => hash('sha256', $request->getClientIp() . $this->appSalt),
            'created_at' => new \DateTimeImmutable()
        ];

        return $this->repository->insertUserAgentEvent($map);
    }
}
