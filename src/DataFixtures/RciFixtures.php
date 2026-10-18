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

use App\Entity\Rci;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-09-25 : fixtures pour rci.
 * Contrat :
 *  - RciRepositoryTest::testSelectRci  : findBy(mavenKey) → 1 ligne (LIMIT 1)
 *  - RciRepositoryTest::testDeleteRciMavenKey : présence de lignes pour la mavenKey
 *  - RciRepositoryTest::testInsertRci  : insert après delete → code 200
 * Seed 3 rows à compteurs nuls, dates différentes pour respecter l'ORDER BY DESC.
 */

class RciFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
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
            $rci = (new Rci())
                ->setMavenKey(self::MAVEN_KEY)
                ->setNcloc(0)
                ->setWeight(0)
                ->setIndicator(null)
                ->setRating(null)
                ->setFrontendNcloc(0)
                ->setFrontendWeight(0)
                ->setFrontendIndicator(null)
                ->setFrontendRating(null)
                ->setBackendNcloc(0)
                ->setBackendWeight(0)
                ->setBackendIndicator(null)
                ->setBackendRating(null)
                ->setAutreNcloc(0)
                ->setAutreWeight(0)
                ->setAutreIndicator(null)
                ->setAutreRating(null)
                ->setInconnuNcloc(0)
                ->setInconnuWeight(0)
                ->setInconnuIndicator(null)
                ->setInconnuRating(null)
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($date);
            $manager->persist($rci);
        }

        $manager->flush();
    }
}
