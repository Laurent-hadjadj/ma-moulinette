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

use App\Entity\Accessibility;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-10-27 : fixtures pour accessibility, même contrat que RciFixtures.php :
 *  - AccessibilityRepositoryTest::testSelectAccessibilityReturnsMostRecentFixtureRow :
 *    selectAccessibility(mavenKey) → la ligne la plus récente (LIMIT 1).
 * Seed 3 rows jamais collectées (compteurs/notes null), dates différentes pour
 * respecter l'ORDER BY DESC.
 */

class AccessibilityFixtures extends Fixture
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
            $accessibility = (new Accessibility())
                ->setMavenKey(self::MAVEN_KEY)
                ->setAccessibilityAnomalies(null)
                ->setAccessibilityDetteMinute(null)
                ->setAccessibilityRating(null)
                ->setAccessibilityCssAnomalies(null)
                ->setAccessibilityCssDetteMinute(null)
                ->setAccessibilityCssRating(null)
                ->setAccessibilityWebAnomalies(null)
                ->setAccessibilityWebDetteMinute(null)
                ->setAccessibilityWebRating(null)
                ->setAccessibilityJavascriptAnomalies(null)
                ->setAccessibilityJavascriptDetteMinute(null)
                ->setAccessibilityJavascriptRating(null)
                ->setAccessibilityTypescriptAnomalies(null)
                ->setAccessibilityTypescriptDetteMinute(null)
                ->setAccessibilityTypescriptRating(null)
                ->setModeCollecte(self::MODE_COLLECTE)
                ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
                ->setDateEnregistrement($date);
            $manager->persist($accessibility);
        }

        $manager->flush();
    }
}
