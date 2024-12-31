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

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ActivityBatchReport;

/**
 * [Description ActivityReportService]
 */
class ActivityReportService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * [Description for generateReport]
     *
     * @param array $infos
     * @param int $successfulTasksCount
     * @param array $errors
     *
     * @return void
     *
     * Created at: 31/12/2024 16:28:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function generateReport(array $infos, int $successfulTasksCount, array $errors): void
    {
        $report = new ActivityBatchReport();
            $report->setDateStart($infos['date_start']);
            $report->setDateEnd($infos['date_end']);
            $report->setTaskCount($infos['task_count']);
            $report->setTaskDone($successfulTasksCount);
            $report->setPage($infos['page']);
            $report->setLastError($errors);
            $report->setDateEnregistrement(new \DateTimeImmutable());

            $this->em->persist($report);
            $this->em->flush();
    }
}
