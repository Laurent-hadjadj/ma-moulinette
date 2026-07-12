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

use App\Entity\Anomalie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création AnomalieFixtures.
 * Contrat :
 *  - AnomalieKernelTest::testAnomalieCount : findBy(mavenKey) -> 3 lignes
 *  - AnomalieRepositoryTest::testSelectAnomalieByProjectName / testSelectAnomalie /
 *    testDeleteAnomalieMavenKey : présence de lignes pour la mavenKey de référence
 * Seed 3 rows à compteurs nuls (PositiveOrZero) ; chaînes de dette obligatoires
 * (NotBlank) avec valeurs simples.
 */

class AnomalieFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const PROJECT_NAME = 'ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        for ($i = 0; $i < 3; $i++) {
            $anomalie = (new Anomalie())
                ->setMavenKey(self::MAVEN_KEY)
                ->setProjectName(self::PROJECT_NAME)
                ->setAnomalieTotal(0)
                ->setDetteMinute(0)
                ->setDetteReliabilityMinute(0)
                ->setDetteVulnerabilityMinute(0)
                ->setDetteCodeSmellMinute(0)
                ->setDetteReliability('0h:0min')
                ->setDetteVulnerability('0h:0min')
                ->setDette('0h:0min')
                ->setDetteCodeSmell('0h:0min')
                ->setFrontend(0)
                ->setBackend(0)
                ->setAutre(0)
                ->setInconnu(0)
                ->setBlocker(0)
                ->setCritical(0)
                ->setMajor(0)
                ->setInfo(0)
                ->setMinor(0)
                ->setBug(0)
                ->setVulnerability(0)
                ->setCodeSmell(0)
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($now);
            $manager->persist($anomalie);
        }

        $manager->flush();
    }
}
