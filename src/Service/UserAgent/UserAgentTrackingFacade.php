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

use Psr\Log\LoggerInterface;
use App\Service\UserAgent\UserAgentTrackerService;

/**
 * [Description UserAgentTrackingFacade]
 */
class UserAgentTrackingFacade
{
    public function __construct(
        private UserAgentTrackerService $tracker,
        private LoggerInterface $logger
    ) {}

    /**
     * [Description for track]
     * Enregistre un événement User-Agent sans impacter le flux applicatif
     * @param string $eventType
     *
     * @return void
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function track(string $eventType): void
    {
        $result = $this->tracker->track($eventType);

        if ($result['code'] !== 200) {
            $this->logger->warning('[UserAgentTracking] Échec tracking', [
                'event_type' => $eventType,
                'erreur' => $result['erreur'],
            ]);
        }
    }
}
