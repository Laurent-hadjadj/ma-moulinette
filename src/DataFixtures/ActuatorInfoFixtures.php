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
use App\Entity\ActuatorInfo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

/* MODIF 2026-05-08 : création ActuatorInfoFixtures.
 * Contrat ActuatorInfoKernelTest :
 *  - testActuatorInfoFindOneBy : findOneBy actuator = 5 (id du parent attendu).
 *  - testActuatorInfoCount     : findAll = 4.
 *
 * MODIF 2026-05-16 : setval rendu CONDITIONNEL.
 * Pourquoi : ActuatorFixtures s'execute AVANT (ordre alphabetique) sur
 * doctrine:fixtures:load complet, et insère 4 Actuators (ids 1-4). Le
 * setval(1, false) inconditionnel d'origine provoquait alors une collision
 * PK (id=1 reste a créer par le SERIAL).
 * Solution : ne reset la sequence QUE si la table actuator est vide
 *   (= contexte isole ActuatorInfoKernelTest avec son propre purger).
 * En contexte series : pas de reset, le 5e Actuator prend l'id 5 naturellement
 * (4 deja insères + 1 = 5).
 */
class ActuatorInfoFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        if ($manager instanceof EntityManagerInterface) {
            $connection = $manager->getConnection();
            if ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                $existingCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM ma_moulinette.actuator');
                if ($existingCount === 0) {
                    $connection->executeStatement("SELECT setval('ma_moulinette.actuator_id_seq', 1, false);");
                }
            }
        }

        $now = new \DateTimeImmutable('2026-01-01 00:00:00');
        $actuators = [];

        for ($i = 1; $i <= 5; $i++) {
            $suffix = sprintf('%02d', $i);
            $actuator = new Actuator();
            $actuator->setMavenKey('fr.ma-moulinette:actuator-info-app' . $i);
            $actuator->setNomApplication('Application Info ' . $suffix);
            $actuator->setUrl('https://app-info' . $i . '.ma-moulinette.fr/actuator');
            $actuator->setActuatorUser('actuator-info-user-' . $i);
            $actuator->setActuatorPassword('actuator-info-password-' . $i);
            $actuator->setPersonne('Responsable Info ' . $suffix);
            $actuator->setDateEnregistrement($now);
            $manager->persist($actuator);
            $actuators[$i] = $actuator;
        }

        // 4 ActuatorInfos : liens vers les Actuators d'id 1, 2, 3 et 5.
        $infoSpecs = [
            1 => ['Version application 01', '1.0.1'],
            2 => ['Version application 02', '1.0.2'],
            3 => ['Version application 03', '1.0.3'],
            5 => ['Version application 05', '1.0.5'],
        ];

        foreach ($infoSpecs as $actuatorIndex => [$desc, $value]) {
            $info = new ActuatorInfo();
            $info->setActuatorInfoDescription($desc);
            $info->setActuatorInfoValue($value);
            $actuators[$actuatorIndex]->addActuatorInfo($info);
            $manager->persist($info);
        }

        $manager->flush();
    }
}
