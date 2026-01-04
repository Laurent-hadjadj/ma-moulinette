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
use App\Repository\UserAgentAnalysisRepository;
use Psr\Log\LoggerInterface;
use DeviceDetector\DeviceDetector;

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
     * Lance un batch d'analyse des User-Agents
     */
    public function runBatch(int $limit = 20): array
    {
        $eventsResult = $this->eventRepository->selectPendingEvents($limit);
        if ($eventsResult['code'] !== 200)
        {
            $this->logger->error('[Run-Batch] ❌ Échec de la requête selectPendingEvents().', [
                'code' => $eventsResult['code'],
                'error' => $eventsResult['error']
            ]);
            return $eventsResult;
        }

        $processed = 0;
        $errors = [];

        /* si la liste est vide en renvoie la réponse avec un code => 200 */
        foreach ($eventsResult['liste'] as $event) {

            $lock = $this->eventRepository->updateProcessingStatus(
                $event['id'],
                'PROCESSING'
            );

            if ($lock['code'] !== 200) {
                $errors[] = [
                    'event_id' => $event['id'],
                    'error' => 'Impossible de verrouiller l’événement'
                ];
                continue;
            }

            try {
                $analysisMap = $this->analyzeUserAgent($event['user_agent']);
                $analysisMap['user_agent_event_id'] = $event['id'];
                $analysisMap['event_type'] = $event['event_type'];
                $analysisMap['url'] = $event['url'];
                $analysisMap['session_id'] = $event['session_id'];
                $analysisMap['detector_version'] = $this->detectorVersion;
                $analysisMap['created_at'] = new \DateTimeImmutable();

                $insert = $this->analysisRepository->insertUserAgentAnalysis($analysisMap);
                    if ($insert['code'] !== 200) {
                        $this->logger->error('[Run-Batch] ❌ Échec de la requête insertUserAgentAnalysis().', [
                            'code' => $insert['code'],
                            'error' => $insert['error']
                        ]);
                    throw new \RuntimeException($insert['error']);
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

                $errors[] = [
                    'event_id' => $event['id'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'code' => 200,
            'processed' => $processed,
            'errors' => $errors
        ];
    }

    /**
     * Analyse un User-Agent via Matomo DeviceDetector
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
