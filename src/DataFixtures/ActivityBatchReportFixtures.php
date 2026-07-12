<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ActivityBatchReport;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation ActivityBatchReportFixtures
 * Contrat : 1 ligne avec task_count=12 et task_done=11 pour satisfaire
 * ActivityBatchReportKernelTest (findOneBy taskCount=12 : 1 résultat ;
 * findBy taskDone=11 : assertCount(1)).
 * Champs non-nullable : date_start, date_end, task_count, task_done, page,
 * date_enregistrement. Le champ last_error (JSON) est nullable et reste vide.
 */
class ActivityBatchReportFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tz = new \DateTimeZone('Europe/Paris');
        $start = new \DateTimeImmutable('2026-01-01 00:00:00', $tz);
        $end = new \DateTimeImmutable('2026-01-01 00:05:00', $tz);
        $now = new \DateTimeImmutable('2026-01-01 00:05:30', $tz);

        $report = (new ActivityBatchReport())
            ->setDateStart($start)
            ->setDateEnd($end)
            ->setTaskCount(12)
            ->setTaskDone(11)
            ->setPage(1)
            ->setLastError([])
            ->setDateEnregistrement($now);

        $manager->persist($report);

        $manager->flush();
    }
}
