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

use App\Entity\NoSonar;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création NoSonarFixtures.
 * Contrat :
 *  - NoSonarKernelTest::testNoSonarCount : findBy(mavenKey) -> 3 lignes
 *  - NoSonarRepositoryTest::testSelectNoSonarRuleGroupByRule : groupBy rule -> 3 règles distinctes
 *    permettent de vérifier l'agrégation
 * Seed 3 rows avec règles distinctes (java:S1309, java:S125, typescript:S6582).
 */

class NoSonarFixtures extends Fixture
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $rows = [
            ['rule' => 'java:S1309',       'component' => 'fr.ma-moulinette:ma-moulinette:src/main/java/fr/example/ServiceA.java', 'line' => 118],
            ['rule' => 'java:S125',        'component' => 'fr.ma-moulinette:ma-moulinette:src/main/java/fr/example/ServiceB.java', 'line' => 42],
            ['rule' => 'typescript:S6582', 'component' => 'fr.ma-moulinette:ma-moulinette:src/app/component-c.ts',                 'line' => 17],
        ];

        foreach ($rows as $row) {
            $noSonar = (new NoSonar())
                ->setMavenKey(self::MAVEN_KEY)
                ->setRule($row['rule'])
                ->setComponent($row['component'])
                ->setLine($row['line'])
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($now);
            $manager->persist($noSonar);
        }

        $manager->flush();
    }
}
