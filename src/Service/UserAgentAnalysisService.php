<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service;

use App\Repository\UserAgentEventRepository;
use App\Repository\UserAgentAnalysisRepository;
use Psr\Log\LoggerInterface;
use DeviceDetector\DeviceDetector;
use App\Exception\SqlRequestException;

/**
 * [Description UserAgentAnalysisService]
 */
class UserAgentAnalysisService
{
    private string $detectorVersion;

    public function __construct(
        private readonly UserAgentEventRepository $eventRepository,
        private readonly UserAgentAnalysisRepository $analysisRepository,
        private LoggerInterface $logger
    ) {
        $this->detectorVersion = DeviceDetector::VERSION;
    }


    /**
     * [Description for runBatch]
     * Lance un batch d'analyse des User-Agents
     *
     * @param int $limit
     *
     * @return array
     *
     * Created at: 13/03/2026 13:01:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function runBatch(int $limit = 50): array
    {
        $eventsResult = $this->eventRepository->selectPendingEvents($limit);
        if ($eventsResult['code'] !== 200)
        {
            $this->logger->error('[Run-Batch] ❌ Échec de la requête selectPendingEvents().', [
                'code' => $eventsResult['code'],
                'erreur' => $eventsResult['erreur']
            ]);
            return $eventsResult;
        }

        $processed = 0;
        $erreurs = [];

        /* si la liste est vide en renvoie la réponse avec un code => 200 */
        foreach ($eventsResult['liste'] as $event) {

            $lock = $this->eventRepository->updateProcessingStatus(
                $event['id'],
                'PROCESSING'
            );

            if ($lock['code'] !== 200) {
                $erreurs[] = [
                    'event_id' => $event['id'],
                    'erreur' => 'Impossible de verrouiller l’événement'
                ];
                continue;
            }

            try {
                $analysisMap = $this->analyzeUserAgent($event['user_agent']);
                $analysisMap['event_type'] = $event['event_type'];
                $analysisMap['url'] = $event['url'];
                $analysisMap['session_id'] = $event['session_id'];
                $analysisMap['visitor_id'] = $event['visitor_id'];
                $analysisMap['user_id'] = $event['user_id'];
                $analysisMap['detector_version'] = $this->detectorVersion;
                $analysisMap['created_at'] = new \DateTimeImmutable();

                $insert = $this->analysisRepository->insertUserAgentAnalysis($analysisMap);
                    if ($insert['code'] !== 200) {
                        $this->logger->error('[Run-Batch] ❌ Échec de la requête insertUserAgentAnalysis().', [
                            'code' => $insert['code'],
                            'erreur' => $insert['erreur']
                        ]);
                    throw new SqlRequestException('insertUserAgentAnalysis', (int) $insert['code'], $insert['erreur'], null);
                }

                $this->eventRepository->updateProcessingStatus(
                    $event['id'],
                    'DONE',
                    new \DateTimeImmutable()
                );

                $processed++;

            } catch (\Throwable $e) {

                $this->eventRepository->updateProcessingStatus(
                    $event['id'],
                    'ERROR',
                    new \DateTimeImmutable()
                );

                $erreurs[] = [
                    'event_id' => $event['id'],
                    'erreur' => $e->getMessage()
                ];
            }
        }

        return [
            'code' => 200,
            'processed' => $processed,
            'erreurs' => $erreurs
        ];
    }

    /**
     * [Description for analyzeUserAgent]
     * Analyse un User-Agent via Matomo DeviceDetector
     *
     * @param string $userAgent
     *
     * @return array
     *
     * Created at: 13/03/2026 12:58:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function analyzeUserAgent(string $userAgent): array
    {
        $dd = new DeviceDetector($userAgent);
        $dd->parse();

        return [
            'device_type' => $dd->getDeviceName(),
            'os_name' => $dd->getOs('name'),
            'os_version' => $dd->getOs('version'),
            'browser_name' => $dd->getClient('name'),
            'browser_version' => $dd->getClient('version'),
            'is_bot' => $dd->isBot(),
        ];
    }
}
