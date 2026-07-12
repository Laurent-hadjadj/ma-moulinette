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

use App\Entity\HotspotDetails;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création HotspotDetailsFixtures.
 * Contrat :
 *  - HotspotDetailsKernelTest::testHotspotDetailsCount : findBy(mavenKey) -> 3 lignes
 *  - HotspotDetailsRepositoryTest::testSelectHotspotDetailsByStatus : au moins une ligne
 *    par statut (TO_REVIEW + REVIEWED)
 * Seed 3 rows variés (frontend / backend / autre) pour couvrir les agrégations.
 */

class HotspotDetailsFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $rows = [
            [
                'hotspotKey' => 'AZCc06XbgfifxdiJPzw1',
                'category' => 'dos',
                'rule' => 'typescript:S5852',
                'ruleName' => 'Slow regex are security-sensitive',
                'severity' => 'MEDIUM',
                'status' => 'TO_REVIEW',
                'resolution' => null,
                'niveau' => 1,
                'frontend' => 1,
                'backend' => 0,
                'autre' => 0,
                'fileName' => 'service-worker.ts',
                'filePath' => 'angular/src/service-worker.ts',
                'line' => 60,
                'message' => 'Make sure the regex used here is safe.',
            ],
            [
                'hotspotKey' => 'AZCc06XbgfifxdiJPzw2',
                'category' => 'sql-injection',
                'rule' => 'java:S2077',
                'ruleName' => 'SQL queries should not be vulnerable to injection',
                'severity' => 'HIGH',
                'status' => 'REVIEWED',
                'resolution' => 'FIXED',
                'niveau' => 2,
                'frontend' => 0,
                'backend' => 1,
                'autre' => 0,
                'fileName' => 'UserDao.java',
                'filePath' => 'backend/src/main/java/fr/example/UserDao.java',
                'line' => 120,
                'message' => 'Make sure this SQL query is safe.',
            ],
            [
                'hotspotKey' => 'AZCc06XbgfifxdiJPzw3',
                'category' => 'sensitive-data',
                'rule' => 'java:S2257',
                'ruleName' => 'Standard cryptographic algorithms should be used',
                'severity' => 'LOW',
                'status' => 'TO_REVIEW',
                'resolution' => null,
                'niveau' => 3,
                'frontend' => 0,
                'backend' => 0,
                'autre' => 1,
                'fileName' => 'CryptoUtil.java',
                'filePath' => 'shared/src/main/java/fr/example/CryptoUtil.java',
                'line' => 42,
                'message' => 'Use a standard cryptographic algorithm.',
            ],
        ];

        foreach ($rows as $row) {
            $details = (new HotspotDetails())
                ->setMavenKey(self::MAVEN_KEY)
                ->setVersion('1.0.0-RELEASE')
                ->setDateVersion($now)
                ->setSecurityCategory($row['category'])
                ->setRuleKey($row['rule'])
                ->setRuleName($row['ruleName'])
                ->setSeverity($row['severity'])
                ->setStatus($row['status'])
                ->setResolution($row['resolution'])
                ->setNiveau($row['niveau'])
                ->setFrontend($row['frontend'])
                ->setBackend($row['backend'])
                ->setAutre($row['autre'])
                ->setFileName($row['fileName'])
                ->setFilePath($row['filePath'])
                ->setLine($row['line'])
                ->setMessage($row['message'])
                ->setHotspotKey($row['hotspotKey'])
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($now);
            $manager->persist($details);
        }

        $manager->flush();
    }
}
