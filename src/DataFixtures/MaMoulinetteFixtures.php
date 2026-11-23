<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\MaMoulinette;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * MODIF 2026-05-08 : création MaMoulinetteFixtures.
 * Contrat :
 *  - MaMoulinetteKernelTest : findOneBy version = '1.0.0' (1 ligne).
 *  - MaMoulinetteRepositoryTest : getMaMoulinetteVersion (code 200, erreur vide).
 * Constructeur (version) initialise dateVersion + dateEnregistrement.
 */

class MaMoulinetteFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $version1 = new MaMoulinette('1.0.0');
        $manager->persist($version1);

        $version2 = new MaMoulinette('1.1.0');
        $manager->persist($version2);

        $manager->flush();
    }
}
