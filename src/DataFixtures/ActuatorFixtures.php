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

use App\Entity\Actuator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création ActuatorFixtures.
 * Contrat ActuatorKernelTest :
 *  - testActuatorFindOneBy : findOneBy mavenKey 'fr.ma-moulinette:app4' +
 *    nomApplication 'Application 04'.
 *  - testActuatorCount     : findAll = 4.
 * 4 actuators (app1..app4). Chaque Actuator porte au moins un ActuatorInfo (Assert\Count
 * min:1 + cascade persist) pour passer la validation.
 */
class ActuatorFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $suffix = sprintf('%02d', $i);
            $actuator = new Actuator();
            $actuator->setMavenKey('fr.ma-moulinette:app' . $i);
            $actuator->setNomApplication('Application ' . $suffix);
            $actuator->setUrl('https://app' . $i . '.ma-moulinette.fr/actuator');
            $actuator->setActuatorUser('actuator-user-' . $i);
            $actuator->setActuatorPassword('actuator-password-' . $i);
            $actuator->setPersonne('Responsable ' . $suffix);
            $actuator->setDateEnregistrement(new \DateTimeImmutable('2026-01-01 00:00:00'));

            // Au moins un info par actuator (cascade persist depuis Actuator).
            $info = new \App\Entity\ActuatorInfo();
            $info->setActuatorInfoDescription('Version application ' . $suffix);
            $info->setActuatorInfoValue('1.0.' . $i);
            $actuator->addActuatorInfo($info);

            $manager->persist($actuator);
        }

        $manager->flush();
    }
}
