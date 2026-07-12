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

use App\Entity\Portefeuille;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création PortefeuilleFixtures pour la vague F.
 * Contrat : 3 portefeuilles dont « MES PROJETS » exigé par PortefeuilleKernelTest
 * (findOneBy portefeuille = MES PROJETS) et PorteFeuilleRepositoryTest
 * (selectPortefeuille map portefeuille = MES PROJETS).
 * Les noms de portefeuille sont uniques (contrainte BDD), `liste` est un JSON non-null.
 */

class PortefeuilleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');

        $portefeuille1 = (new Portefeuille())
            ->setPortefeuille('MES PROJETS')
            ->setGroupeFonctionnel('Direction Technique')
            ->setListe(['fr.ma-moulinette:app', 'fr.ma-moulinette:2048-spring'])
            ->setDateEnregistrement($now);
        $manager->persist($portefeuille1);

        $portefeuille2 = (new Portefeuille())
            ->setPortefeuille('PROJETS LEGACY')
            ->setGroupeFonctionnel('Direction des Opérations')
            ->setListe(['fr.ma-moulinette:legacy-app'])
            ->setDateEnregistrement($now);
        $manager->persist($portefeuille2);

        $portefeuille3 = (new Portefeuille())
            ->setPortefeuille('PROJETS R&D')
            ->setGroupeFonctionnel('Direction Innovation')
            ->setListe([])
            ->setDateEnregistrement($now);
        $manager->persist($portefeuille3);

        $manager->flush();
    }
}
