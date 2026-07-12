<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\CleanCode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-17 : fixtures pour clean_code.
 * Contrat :
 *  - CleanCodeRepositoryTest::testSelectCleanCode  : findBy(mavenKey) → 1 ligne (LIMIT 1)
 *  - CleanCodeRepositoryTest::testDeleteCleanCodeMavenKey : présence de lignes pour la mavenKey
 *  - CleanCodeRepositoryTest::testInsertCleanCode  : insert après delete → code 200
 * Seed 3 rows à compteurs nuls, dates différentes pour respecter l'ORDER BY DESC.
 */

class CleanCodeFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const PROJECT_NAME = 'ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $dates = [
            new \DateTimeImmutable('2026-01-01 00:00:00'),
            new \DateTimeImmutable('2026-02-01 00:00:00'),
            new \DateTimeImmutable('2026-03-01 00:00:00'),
        ];

        foreach ($dates as $date) {
            $cc = (new CleanCode())
                ->setMavenKey(self::MAVEN_KEY)
                ->setProjectName(self::PROJECT_NAME)
                ->setIssueTotal(0)
                ->setCcConsistent(0)
                ->setCcIntentional(0)
                ->setCcAdaptable(0)
                ->setCcResponsible(0)
                ->setQualityMaintainability(0)
                ->setQualityReliability(0)
                ->setQualitySecurity(0)
                ->setImpactBlocker(0)
                ->setImpactHigh(0)
                ->setImpactMedium(0)
                ->setImpactLow(0)
                ->setImpactInfo(0)
                ->setOwaspTop10(0)
                ->setSansTop25(0)
                ->setCwe(0)
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($date);
            $manager->persist($cc);
        }

        $manager->flush();
    }
}
