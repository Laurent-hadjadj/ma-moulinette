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

use App\Entity\Activity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation ActivityFixtures.
 * Contrat : 2 lignes Activity sur la maven_key fr.ma-moulinette:ma-moulinette
 * pour satisfaire ActivityKernelTest::testActivityCount (assertCount(2) sur findBy maven_key)
 * et ActivityKernelTest::testActivityFindOneBy. Les dates sont positionnées sur 2024 pour
 * faire passer les requêtes ActivityRepositoryTest (selectActivity, dernierDate,
 * premiereDate, listeProjectAnalyse... sur year=2024).
 * Champs non-nullable : maven_key, project_name, analyse_id (max 26 chars), status,
 * submitter_login, submitted_at, started_at, executed_at, execution_time.
 */

class ActivityFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const PROJECT_NAME = 'ma-moulinette';
    private const SUBMITTER = 'laurent.hadjadj';

    public function load(ObjectManager $manager): void
    {
        $tz = new \DateTimeZone('Europe/Paris');

        $first = (new Activity())
            ->setMavenKey(self::MAVEN_KEY)
            ->setProjectName(self::PROJECT_NAME)
            ->setAnalyseId('AYz0001abcdefghijKLmnop')
            ->setStatus('SUCCESS')
            ->setSubmitterLogin(self::SUBMITTER)
            ->setSubmittedAt(new \DateTimeImmutable('2024-07-31 12:26:58', $tz))
            ->setStartedAt(new \DateTimeImmutable('2024-07-31 12:27:05', $tz))
            ->setExecutedAt(new \DateTimeImmutable('2024-07-31 12:27:47', $tz))
            ->setExecutionTime(42);
        $manager->persist($first);

        $second = (new Activity())
            ->setMavenKey(self::MAVEN_KEY)
            ->setProjectName(self::PROJECT_NAME)
            ->setAnalyseId('AYz0002abcdefghijKLmnop')
            ->setStatus('SUCCESS')
            ->setSubmitterLogin(self::SUBMITTER)
            ->setSubmittedAt(new \DateTimeImmutable('2024-08-15 09:10:00', $tz))
            ->setStartedAt(new \DateTimeImmutable('2024-08-15 09:10:05', $tz))
            ->setExecutedAt(new \DateTimeImmutable('2024-08-15 09:10:55', $tz))
            ->setExecutionTime(50);
        $manager->persist($second);

        $manager->flush();
    }
}
