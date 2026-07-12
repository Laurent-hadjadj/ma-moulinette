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

use App\Entity\AnomalieDetails;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création AnomalieDetailsFixtures.
 * Contrat :
 *  - AnomalieDetailsKernelTest::testAnomalieDetailsCount : findBy(mavenKey) -> 3 lignes
 *  - AnomalieDetailsRepositoryTest::testSelectAnomalieDetailsMavenKey /
 *    testDeleteAnomalieDetailsMavenKey : lignes pour la mavenKey de référence
 * Seed 3 rows à compteurs nuls (15 PositiveOrZero : bug/vuln/codeSmell × Blocker/Critical/Info/Major/Minor).
 */

class AnomalieDetailsFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const PROJECT_NAME = 'ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        for ($i = 0; $i < 3; $i++) {
            $details = (new AnomalieDetails())
                ->setMavenKey(self::MAVEN_KEY)
                ->setName(self::PROJECT_NAME)
                ->setBugBlocker(0)
                ->setBugCritical(0)
                ->setBugInfo(0)
                ->setBugMajor(0)
                ->setBugMinor(0)
                ->setVulnerabilityBlocker(0)
                ->setVulnerabilityCritical(0)
                ->setVulnerabilityInfo(0)
                ->setVulnerabilityMajor(0)
                ->setVulnerabilityMinor(0)
                ->setCodeSmellBlocker(0)
                ->setCodeSmellCritical(0)
                ->setCodeSmellInfo(0)
                ->setCodeSmellMajor(0)
                ->setCodeSmellMinor(0)
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($now);
            $manager->persist($details);
        }

        $manager->flush();
    }
}
