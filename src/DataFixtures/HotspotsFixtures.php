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

use App\Entity\Hotspots;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création HotspotsFixtures.
 * Contrat :
 *  - HotspotsKernelTest::testHotspotsCount : findBy(mavenKey) -> 3 lignes
 *  - HotspotsRepositoryTest::testCountHotspotsStatus : 2 statuts TO_REVIEW + REVIEWED
 *  - HotspotsRepositoryTest::testSelectHotspotsByNiveau : niveaux variés
 * Seed 3 rows avec hotspot_key distincts (clé hotspot non unique en BDD mais on
 * garde une variation pour réalisme).
 */

class HotspotsFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $rows = [
            ['hotspotKey' => 'AZCc06XbgfifxdiJPzw1', 'category' => 'dos',           'rule' => 'typescript:S5852', 'probability' => 'MEDIUM', 'status' => 'TO_REVIEW', 'resolution' => null,   'niveau' => 1],
            ['hotspotKey' => 'AZCc06XbgfifxdiJPzw2', 'category' => 'sql-injection', 'rule' => 'java:S2077',       'probability' => 'HIGH',   'status' => 'REVIEWED',  'resolution' => 'FIXED', 'niveau' => 2],
            ['hotspotKey' => 'AZCc06XbgfifxdiJPzw3', 'category' => 'sensitive-data', 'rule' => 'java:S2257',       'probability' => 'LOW',    'status' => 'TO_REVIEW', 'resolution' => null,   'niveau' => 3],
        ];

        foreach ($rows as $row) {
            $hotspot = (new Hotspots())
                ->setMavenKey(self::MAVEN_KEY)
                ->setVersion('1.0.0-RELEASE')
                ->setDateVersion($now)
                ->setHotspotKey($row['hotspotKey'])
                ->setSecurityCategory($row['category'])
                ->setRuleKey($row['rule'])
                ->setProbability($row['probability'])
                ->setStatus($row['status'])
                ->setResolution($row['resolution'])
                ->setNiveau($row['niveau'])
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($now);
            $manager->persist($hotspot);
        }

        $manager->flush();
    }
}
