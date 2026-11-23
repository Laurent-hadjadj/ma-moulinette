<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Service\UserAgent;

use App\Entity\Utilisateur;
use App\Repository\UserAgentEventRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

/**
 * [Description UserAgentTrackerService]
 */
class UserAgentTrackerService
{
    public function __construct(
        private RequestStack $requestStack,
        private UserAgentEventRepository $repository,
        private Security $security,
        private string $appSalt //à définir dans service.yml
    ) {}


    /**
     * [Description for track]
     * Collecte et enregistre un événement User-Agent.
     *
     * @param string $eventType Type fonctionnel de l'événement (LOGIN_PAGE_VIEW, LOGIN_SUCCESS_REDIRECT, LOGOUT)
     *
     * @return array{code: int, erreur: string} ['code'=>200,'erreur'=>null] ou erreur
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function track(string $eventType): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [
                'code' => 500,
                'erreur' => 'Pas de requête active'
            ];
        }

        $sessionId = $request->getSession()->getId();
        $user = $this->security->getUser();
        // MODIF 2026-06-09 [user-agent-init]: UserInterface n'a pas getId() — cast explicite
        $userId = ($user instanceof Utilisateur) ? $user->getId() : null;
        $visitorId = $request->cookies->get('visitor_id');

        if (!$visitorId) {
            $visitorId = (string) new Ulid();
            $request->attributes->set('visitor_id', $visitorId);
        }

        $map = [
            'event_type' => $eventType,
            'url' => $request->getPathInfo(),
            'user_agent' => $request->headers->get('User-Agent'),
            'session_id' => $sessionId,
            'user_id' => $userId,
            'visitor_id' => $visitorId,
            'auth_state' => $user ? 'AUTHENTICATED' : 'ANONYMOUS',
            'processing_status' => 'PENDING',
            'ip_hash' => hash('sha256', $request->getClientIp() . $this->appSalt),
            'created_at' => new \DateTimeImmutable()
        ];

        return $this->repository->insertUserAgentEvent($map);
    }
}
