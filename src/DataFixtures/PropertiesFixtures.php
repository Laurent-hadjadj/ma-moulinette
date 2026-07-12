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

use App\Entity\Properties;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création PropertiesFixtures.
 * Contrat :
 *  - PropertiesKernelTest : findOneBy type = 'properties' (1 ligne attendue).
 *  - PropertiesRepositoryTest : getProperties / insertProperties / updatePropertiesProjet
 *    / updatePropertiesProfiles — tous attendent code 200 + erreur vide.
 * Une seule ligne avec type=properties suffit (les autres types valident la diversité).
 */
class PropertiesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-01 00:00:00');
        $modProjet = new \DateTime('2025-12-15 10:00:00');
        $modProfil = new \DateTime('2025-12-20 16:30:00');

        $p1 = (new Properties())
            ->setType('properties')
            ->setProjetBd(50)
            ->setProjetSonar(50)
            ->setProfilBd(8)
            ->setProfilSonar(8)
            ->setDateCreation($now)
            ->setDateModificationProjet($modProjet)
            ->setDateModificationProfil($modProfil);
        $manager->persist($p1);

        $manager->flush();
    }
}
