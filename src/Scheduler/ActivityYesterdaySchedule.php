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

namespace App\Scheduler;

use App\Message\ActivityMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

/**
 * [Description ActivityYesterdaySchedule]
 */
#[AsSchedule('activity_yesterday')]
final class ActivityYesterdaySchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
        $this->logger = $logger;
    }

    /**
     * [Description for getYesterdayDates]
     *
     * @return array
     *
     * Created at: 27/12/2024 13:53:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getYesterdayDates(): array
    {
        $yesterday = new \DateTimeImmutable('yesterday', new \DateTimeZone('Europe/Paris'));
        return [
            'fromDate' => $yesterday->format('Y-m-d 00:00:00'),
            'toDate' => $yesterday->format('Y-m-d 23:59:59'),
        ];
    }

    /**
     * [Description for getSchedule]
     *
     * @return Schedule
     *
     * Created at: 26/12/2024 11:59:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSchedule(): Schedule
    {
        $cacheKey = 'activity_quotidienne_execution';
        $cacheItem = $this->cache->get($cacheKey, function (ItemInterface $item) {
            // Si la tâche n'est pas en cours, on peut la planifier
            $item->expiresAfter(5 * 60);  // Expires after 5 minutes
            return false; // Pas en cours d'exécution
        });

        // Si la tâche est déjà en cours d'exécution, ne pas planifier une nouvelle
        if ($cacheItem) {
            $this->logger->info('[ACTIVITY-SCHEDULER] Une exécution est déjà en cours.');
            return new Schedule(); // Aucune tâche à planifier
        }

        /* Cron pour exécuter à 22h00 chaque jour, on récupère directement le paramètre */
        $cron = $_ENV['DAILY_ACTIVITY_CRON'] ?? '0 22 * * *';

        /** On calcule la date et la plage à J-1 */
        $dates = $this->getYesterdayDates();

        $this->logger->info('[ACTIVITY SCHEDULER] Le programme a été exécuté', [
            'Début' => $dates['fromDate'], 'Fin' => $dates['toDate'], 'cron' => $cron]);

        /* Planification du message */
        return (new Schedule())
            ->add(
                RecurringMessage::cron(
                    $cron,
                    new ActivityMessage($dates['fromDate'], $dates['toDate']),
                    new \DateTimeZone('Europe/Paris')
                )
            )
            // Permet d'assurer qu'une seule instance de la tâche s'exécute
            ->stateful($this->cache);
    }
}
