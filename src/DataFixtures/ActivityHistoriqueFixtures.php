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

use App\Entity\ActivityHistorique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation ActivityHistoriqueFixtures.
 * Contrat : 1 ligne avec year=2024 pour satisfaire
 * ActivityHistoriqueKernelTest::testActivityHistoriqueFindOneBy (findOneBy year=2024 : 1
 * résultat). ActivityHistoriqueRepositoryTest (insert/update/select) ne contrôle que
 * code 200, sans pre-requis particulier sur le contenu.
 * Champs non-nullable : year (positif), day (1..366), analyse, analyseAverage, success,
 * failed, successRate (0..100), maxTime, dateEnregistrement.
 */
class ActivityHistoriqueFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00', new \DateTimeZone('Europe/Paris'));

        $row = (new ActivityHistorique())
            ->setYear(2024)
            ->setDay(326)
            ->setAnalyse(1253)
            ->setAnalyseAverage(87.3)
            ->setSuccess(1249)
            ->setFailed(4)
            ->setSuccessRate(99.0)
            ->setMaxTime(34)
            ->setDateEnregistrement($now);

        $manager->persist($row);

        $manager->flush();
    }
}
