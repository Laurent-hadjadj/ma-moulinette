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

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Activity;
use App\Message\ProcessTaskMessage;

/**
 * [Description ProcessTaskHandler]
 */
#[AsMessageHandler]
final class ProcessTaskHandler
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * [Description for __invoke]
     *
     * @param ProcessTaskMessage $message
     *
     * @return void
     *
     * Created at: 27/12/2024 14:54:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
        public function __invoke(ProcessTaskMessage $message): void
        {
        $task = $message->getTask();

        $this->logger->info('ℹ️ [ACTIVITY] Traitement de la tâche', ['taskId' => $task['id']]);

        // Validation : Vérifier si la tâche existe déjà dans la base
        $existingActivity = $this->em->getRepository(Activity::class)->findOneBy(['analyseId' => $task['id']]);
        if ($existingActivity) {
            $this->logger->info('ℹ️ [ACTIVITY] La tâche existe déjà, elle ne sera pas insérée.', [
                'taskId' => $task['id'],
            ]);
            return;
        }

        // Création d'une nouvelle entité Activity
        $activity = new Activity();
            $activity->setAnalyseId($task['id']); // ID unique de la tâche
            $activity->setMavenKey($task['componentId']); // Clé Maven
            $activity->setProjectName($task['componentName']); // Nom du projet
            $activity->setStatus($task['status']); // Statut de la tâche
            $activity->setSubmitterLogin($task['submitterLogin']); // Utilisateur ayant soumis la tâche

            // Mapping des dates
            $activity->setSubmittedAt(new \DateTimeImmutable($task['submittedAt']));
            $activity->setStartedAt(new \DateTimeImmutable($task['startedAt'] ?? $task['submittedAt']));
            $activity->setExecutedAt(new \DateTimeImmutable($task['executedAt']));

            // Calcul du temps d'exécution (en secondes)
            $startTime = new \DateTimeImmutable($task['submittedAt']);
            $endTime = new \DateTimeImmutable($task['executedAt']);
            $executionTime = $endTime->getTimestamp() - $startTime->getTimestamp();
            $activity->setExecutionTime($executionTime);

            // Enregistrement dans la base
            $this->em->persist($activity);
            $this->em->flush();

        $this->logger->info('ℹ️ [ACTIVITY] Tâche enregistrée dans la table activity', [
            'taskId' => $task['id'],
            'mavenKey' => $task['componentId'],
            'projectName' => $task['componentName'],
        ]);
    }
}
