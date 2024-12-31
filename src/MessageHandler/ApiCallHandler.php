<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\MessageHandler;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;
use Psr\Log\LoggerInterface;
use App\Message\ApiCallMessage;
use App\Message\ProcessTaskMessage;
use App\Service\ActivityReportService;
use App\Service\Client;

#[AsMessageHandler]
/**
 * [Description ApiCallHandler]
 */
final class ApiCallHandler
{

    public function __construct(
        private ActivityReportService $activityReportService,
        private MessageBusInterface $messengerBus,
        private LoggerInterface $logger,
        private ParameterBagInterface $params,
        private Client $client,
    ) {
        $this->activityReportService = $activityReportService;
        $this->messengerBus = $messengerBus;
        $this->logger = $logger;
        $this->params = $params;
        $this->client = $client;
    }

    /**
     * [Description for __invoke]
     *
     * @param ApiCallMessage $message
     *
     * @return void
     *
     * Created at: 31/12/2024 16:10:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __invoke(ApiCallMessage $message): void
    {
        $fromDate = $message->getFromDate();
        $toDate = $message->getToDate();

        $this->logger->info('##########################################################');
        $this->logger->info('ApiCallHandler reçu', ['de' => $fromDate, 'à' => $toDate]);
        $this->logger->info('#########################################################');

        /** Initialisation */
        $infos = $errors = $tasks = [];
        $pageSize = 1000;
        $infos['page'] = $pageIndex = 1;   // en version 9 et plus
        $infos['date_start'] = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $infos['task_count'] = $successfulTasksCount = 0;
        $baseUrl = $this->params->get('sonar.url');
        $urlTemplate = $baseUrl . '/api/ce/activity';

        /** Récupérer les tâches par pages pour SonarQube 8 (les 1000 premiers) pour la version 9 en paginant. */
        do {
            $url = sprintf('%s?%s', $urlTemplate, http_build_query([
                'minSubmittedAt' => $fromDate, 'maxExecutedAt' => $toDate,
                'p' => $pageIndex, 'ps' => $pageSize,
            ]));

            // Étape 1 : Récupérer toutes les tâches (1000).
            try {
                /** On appelle l'API */
                $response = $this->client->httpActivity($url);
                /** On récupère les tâches ou rien */
                $tasks = $response['json']['tasks'] ?? [];

                if (empty($tasks)) {
                    // Aucune tâche récupérée, sortir de la boucle
                    $this->logger->info("[ACTIVITY] - Aucune tâche trouvée à la page $pageIndex.");
                    break;
                }

                $this->logger->info('[ACTIVITY] - Page ' . $pageIndex . ' traitée avec ' . count($response['json']['tasks']) . ' tâches.');
                $infos['task_count'] += count($response['json']['tasks']);

                // Étape 3 : Traiter chaque tâche
                // Traiter les tâches immédiatement pour limiter l'utilisation mémoire
                foreach ($tasks as $task) {
                    try {
                        $processTaskMessage = new ProcessTaskMessage($task);
                        $this->logger->info('[ACTIVITY] - Traitement de la tâche', ['taskId' => $task['id']]);
                        $this->messengerBus->dispatch($this->createEnvelope($processTaskMessage));
                        $this->logger->info('[ACTIVITY] - Message envoyé', ['taskId' => $task['id']]);
                        $successfulTasksCount++;
                    } catch (\Throwable $e) {
                        $this->logger->error('[ACTIVITY] - Erreur dans le traitement des tâches : ' . $e->getMessage());
                        $errors[] = sprintf('[ACTIVITY] - %s', $e->getMessage());
                        break; /** on sort pour éviter la répétition des erreurs */
                    }

                    // Libérer la mémoire après chaque page traitée
                    unset($response['json']['tasks']);
                }
                // Si SonarQube 8, arrêter après la première page (8 = string)
                if ($this->params->get('sonar.version') == 8) {
                    $this->logger->info("[ACTIVITY] - Version de Sonar détectée : " . $this->params->get('sonar.version'));
                    $infos['page']= $pageIndex;
                    break; // Arrêt explicite
                }

                //on attend 5 secondes
                sleep(5);
                $pageIndex++;
                $infos['page']= $pageIndex;
            } catch (\Throwable $e) {
                $this->logger->error('[ACTIVITY] - Erreur lors du traitement de la page ' . $pageIndex . ': ' . $e->getMessage());
                $errors[] = $e->getMessage();
                break; // Arrêter si une erreur se produit
            }
        } while (true);

        $infos['date_end'] =  new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));

        // Étape 4 : Générer le rapport
        $this->activityReportService->generateReport($infos, $successfulTasksCount, $errors);

        $this->logger->info('###########################################################');
        $this->logger->info('[REPORT] Rapport batch enregistré', ['Taches' => count($tasks), 'Publié' => $successfulTasksCount ]);
        $this->logger->info('###########################################################');
        $this->logger->info('#                                                         #');
    }

    /**
     * [Description for createEnvelope]
     *
     * @param ProcessTaskMessage $message
     *
     * @return Envelope
     *
     * Created at: 31/12/2024 16:15:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function createEnvelope(ProcessTaskMessage $message): Envelope
    {
        return new Envelope($message);
    }
}
