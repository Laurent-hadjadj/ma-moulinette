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

use App\Entity\RepartitionTemp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : creation RepartitionTempFixtures.
 * Contrat : 15 lignes RepartitionTemp pour le couple
 * (maven_key=fr.ma-moulinette:ma-moulinette, setup=1000000000001) afin de satisfaire :
 *   - RepartitionTempKernelTest::testRepartitionTempCount (assertCount(15) sur findBy setup),
 *   - RepartitionTempKernelTest::testRepartitionTempFindOneBy (findOneBy maven_key+setup),
 *   - RepartitionTempRepositoryTest::testSelectRepartitionByTypeAndSeveritySuccess
 *     (au moins une ligne BUG/CRITICAL pour ce couple).
 * La grille couvre les 3 categories x 5 sévérités SonarQube (BUG, VULNERABILITY, CODE_SMELL
 * x BLOCKER, CRITICAL, MAJOR, MINOR, INFO) — 15 lignes au total. Tous les champs non-nullable
 * (maven_key, component, type, severity, setup) sont renseignes.
 */

class RepartitionTempFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const COMPONENT = 'fr.ma-moulinette:ma-moulinette:src/Controller/ApiController.php';
    private const SETUP = 1000000000001;

    public function load(ObjectManager $manager): void
    {
        $types = ['BUG', 'VULNERABILITY', 'CODE_SMELL'];
        $severities = ['BLOCKER', 'CRITICAL', 'MAJOR', 'MINOR', 'INFO'];

        foreach ($types as $type) {
            foreach ($severities as $severity) {
                $row = (new RepartitionTemp())
                    ->setMavenKey(self::MAVEN_KEY)
                    ->setComponent(self::COMPONENT)
                    ->setType($type)
                    ->setSeverity($severity)
                    ->setSetup(self::SETUP);

                $manager->persist($row);
            }
        }

        $manager->flush();
    }
}
