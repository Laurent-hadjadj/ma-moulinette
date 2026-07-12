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

use App\Entity\ListeProjet;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création ListeProjetFixtures.
 * Contrat :
 *  - ListeProjetKernelTest : findOneBy mavenKey = fr.ma-moulinette:ma-moulinette (1 ligne).
 *  - ListeProjetRepositoryTest : countListeProjet, countListeProjetVisibility(private),
 *    countListeProjetTags, selectListeProjetByGroupe (clause tag LIKE 'ma-moulinette%' OR
 *    tag LIKE '2048%'), deleteListeProjet (purge). Au moins une ligne private + tags
 *    ma-moulinette / 2048 nécessaires.
 * Constructeur : (mavenKey, name, visibility, tags) — initialise dateEnregistrement.
 */

class ListeProjetFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $projet1 = new ListeProjet(
            'fr.ma-moulinette:ma-moulinette',
            'Ma-Moulinette',
            'public',
            ['ma-moulinette', 'symfony']
        );
        $projet1->setDateEnregistrement($now);
        $manager->persist($projet1);

        $projet2 = new ListeProjet(
            'fr.ma-moulinette:2048-spring',
            '2048 Spring Boot',
            'private',
            ['2048', 'spring-boot']
        );
        $projet2->setDateEnregistrement($now);
        $manager->persist($projet2);

        $projet3 = new ListeProjet(
            'fr.ma-moulinette:legacy-app',
            'Legacy App',
            'private',
            ['legacy']
        );
        $projet3->setDateEnregistrement($now);
        $manager->persist($projet3);

        $manager->flush();
    }
}
