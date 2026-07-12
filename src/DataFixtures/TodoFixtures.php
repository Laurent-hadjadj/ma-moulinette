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

use App\Entity\Todo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création TodoFixtures.
 * Contrat :
 *  - TodoKernelTest : findOneBy mavenKey ma-moulinette + findBy même clé = 3.
 *  - TodoRepositoryTest : deleteTodoMavenKey / selectTodoRuleGroupByRule /
 *    selectTodoComponentOrderByRule / insertTodo — tous attendent code 200.
 *    Trois To.do avec mavenKey identique ; constructeur (vide) initialise dateEnregistrement.
 */

class TodoFixtures extends Fixture
{
    private const MODE_COLLECTE = 'TRAITEMENT MANUEL';
    private const UTILISATEUR_COLLECTE = 'batch.collecte@ma-moulinette.fr';

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');
        $maven = 'fr.ma-moulinette:ma-moulinette';

        $t1 = (new Todo())
            ->setMavenKey($maven)
            ->setRule('java:S1135')
            ->setComponent('fr.ma-moulinette:ma-moulinette:src/main/java/fr/ma/Service.java')
            ->setLine(42)
            ->setModeCollecte(self::MODE_COLLECTE)
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);
        $manager->persist($t1);

        $t2 = (new Todo())
            ->setMavenKey($maven)
            ->setRule('java:S125')
            ->setComponent('fr.ma-moulinette:ma-moulinette:src/main/java/fr/ma/Controller.java')
            ->setLine(81)
            ->setModeCollecte('TRAITEMENT AUTOMATIQUE')
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);
        $manager->persist($t2);

        $t3 = (new Todo())
            ->setMavenKey($maven)
            ->setRule('java:S1135')
            ->setComponent('fr.ma-moulinette:ma-moulinette:src/main/java/fr/ma/Repository.java')
            ->setLine(17)
            ->setModeCollecte('COLLECTE')
            ->setUtilisateurCollecte(self::UTILISATEUR_COLLECTE)
            ->setDateEnregistrement($now);
        $manager->persist($t3);

        $manager->flush();
    }
}
