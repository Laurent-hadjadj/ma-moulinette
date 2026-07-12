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

use App\Entity\OwaspTop10;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création OwaspTop10Fixtures.
 * Contrat :
 *  - OwaspTop10KernelTest::testOwaspTop10FindOneBy : findOneBy(category="A1 - Attaques d'injection")
 *  - OwaspTop10KernelTest::testOwaspCount : findBy(year=2017) doit renvoyer exactement 1 ligne
 *  - OwaspTop10RepositoryTest::testSelectOwaspTop10Referential : 1ère ligne year=2017 category="A1 - Attaques d'injection"
 *  - OwaspTop10RepositoryTest::testSelectOwaspTop10Details : id=1 doit retourner la ligne A1 2017
 * Une seule ligne suffit (testOwaspCount exige assertCount 1).
 */
class OwaspTop10Fixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $a1 = (new OwaspTop10())
            ->setYear(2017)
            ->setCategory("A1 - Attaques d'injection")
            ->setDescription(
                "Les failles d'injection, telles que l'injection SQL, NoSQL, OS et LDAP, "
                . "se produisent lorsque des données non fiables sont envoyées à un interpréteur "
                . "dans le cadre d'une commande ou d'une requête."
            )
            ->setLien('__a01-2017-injection.html.twig')
            ->setDateEnregistrement($now);
        $manager->persist($a1);

        $manager->flush();
    }
}
