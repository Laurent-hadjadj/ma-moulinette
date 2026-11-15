<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
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
     * @param array<int|string, mixed> $infos
     * @param int $successfulTasksCount
     * @param array<int|string, mixed> $errors
     *
     * @return void
     *
     * Created at: 31/12/2024 16:28:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
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
