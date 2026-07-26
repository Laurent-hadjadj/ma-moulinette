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

namespace App\Tests\Integration\Entity;

use Doctrine\ORM\EntityManagerInterface;
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

    private static string $mavenKey = 'fr.ma-moulinette:app4';
    private static string $nomApplication = 'Application 04';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.actuator_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.actuator_info');
        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.actuator');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new ActuatorFixtures()], true);
    }

    public function testActuatorFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $actuatorRepository = $entityManager->getRepository(Actuator::class);
        $response = $actuatorRepository->findOneBy(['mavenKey' => self::$mavenKey, 'nomApplication' => self::$nomApplication]);

        $this->assertNotNull($response, 'Maven_Key: Aucune réponse trouvée');
    }

    public function testActuatorCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $actuatorRepository = $entityManager->getRepository(Actuator::class);
        $response = $actuatorRepository->findAll();

        $this->assertCount(4, $response, 'findAll(): Aucune réponse trouvée');
    }
}
