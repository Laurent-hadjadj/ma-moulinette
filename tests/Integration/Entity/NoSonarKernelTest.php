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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\NoSonarFixtures;
use App\Entity\NoSonar;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description NoSonarKernelTest]
 */
class NoSonarKernelTest extends KernelTestCase
{

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.no_sonar_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.no_sonar');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new NoSonarFixtures()], true);
    }

    public function testNoSonarFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $noSonarRepository = $entityManager->getRepository(NoSonar::class);
        $response = $noSonarRepository->findOneBy(['mavenKey' => self::$mavenKey]);

        $this->assertCount(1, [$response], 'maven_key Aucune réponse trouvée');
    }

    public function testNoSonarCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $noSonarRepository = $entityManager->getRepository(NoSonar::class);
        $response = $noSonarRepository->findBy(['mavenKey' => self::$mavenKey]);

        $this->assertCount(3, $response, 'maven_key Aucune réponse trouvée');
    }
}
