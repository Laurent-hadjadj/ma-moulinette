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

use Psr\Log\LoggerInterface;
use App\Service\UserAgentTrackerService;

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
     * Created at: 14/12/2025 22:46:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function track(string $eventType): void
    {
        $result = $this->tracker->track($eventType);

        if ($result['code'] !== 200) {
            $this->logger->warning('[UserAgentTracking] Échec tracking', [
                'event_type' => $eventType,
                'error' => $result['error'] ?? null,
            ]);
        }
    }
}
