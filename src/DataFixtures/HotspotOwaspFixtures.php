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

use App\Entity\HotspotOwasp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création HotspotOwaspFixtures.
 * Contrat :
 *  - HotspotOwaspKernelTest::testHotspotOwaspCount : findBy(mavenKey) -> 3 lignes
 *  - HotspotOwaspRepositoryTest::testCountHotspotOwaspStatus : statuts TO_REVIEW + REVIEWED
 *  - HotspotOwaspRepositoryTest::testCountHotspotOwaspMenaceByStatus : au moins une ligne avec menace="a1" probability="MEDIUM"
 * Seed 3 rows : (a1/MEDIUM/TO_REVIEW), (a2/HIGH/REVIEWED), (a3/LOW/TO_REVIEW).
 */

class HotspotOwaspFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $rows = [
            ['menace' => 'a1', 'probability' => 'MEDIUM', 'status' => 'TO_REVIEW', 'category' => 'sql-injection',  'rule' => 'java:S2077',     'niveau' => 1],
            ['menace' => 'a2', 'probability' => 'HIGH',   'status' => 'REVIEWED',  'category' => 'auth',           'rule' => 'java:S2257',     'niveau' => 2],
            ['menace' => 'a3', 'probability' => 'LOW',    'status' => 'TO_REVIEW', 'category' => 'sensitive-data', 'rule' => 'typescript:S2068', 'niveau' => 3],
        ];

        foreach ($rows as $row) {
            $hotspot = (new HotspotOwasp())
                ->setReferentialOwasp(2017)
                ->setMavenKey(self::MAVEN_KEY)
                ->setVersion('1.0.0-RELEASE')
                ->setDateVersion($now)
                ->setMenace($row['menace'])
                ->setSecurityCategory($row['category'])
                ->setRuleKey($row['rule'])
                ->setProbability($row['probability'])
                ->setStatus($row['status'])
                ->setResolution(null)
                ->setNiveau($row['niveau'])
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($now);
            $manager->persist($hotspot);
        }

        $manager->flush();
    }
}
