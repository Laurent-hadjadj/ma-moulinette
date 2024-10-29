<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Kernel;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\ActuatorFixtures;
use App\Entity\Actuator;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description ActuatorKernelTest]
 */
class ActuatorKernelTest extends KernelTestCase
{

    private static $mavenKey = 'fr.ma-moulinette:app4';
    private static $nomApplication = 'Application 04';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.actuator_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new ActuatorFixtures()]);
    }

    public function testActuatorFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $actuatorRepository = $entityManager->getRepository(Actuator::class);
        $response = $actuatorRepository->findOneBy(['mavenKey' => static::$mavenKey, 'nomApplication'=>static::$nomApplication]);

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(1, [$response], 'MAVENKEY: Aucune réponse trouvée');
    }

    public function testActuatorCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $anomalieRepository = $entityManager->getRepository(Actuator::class);
        $response = $anomalieRepository->findAll();

        $this->assertNotNull($response, 'Aucune entité a été trouvée');
        $this->assertCount(4, $response, 'findAll(): Aucune réponse trouvée');
    }
}
