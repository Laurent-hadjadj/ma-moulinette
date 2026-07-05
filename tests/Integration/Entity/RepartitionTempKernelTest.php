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
use App\DataFixtures\RepartitionTempFixtures;
use App\Entity\RepartitionTemp;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description RepartitionTempKernelTest]
 */
class RepartitionTempKernelTest extends KernelTestCase
{

    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static int $setup = 1000000000001;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.repartition_temp_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new RepartitionTempFixtures()]);
    }

    public function testRepartitionTempFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $repartitionTempRepository = $entityManager->getRepository(RepartitionTemp::class);
        $response = $repartitionTempRepository->findOneBy(['mavenKey' => self::$mavenKey, 'setup' => self::$setup]);

        $this->assertCount(1, [$response], 'MavenKey & Setup: Aucune réponse trouvée');
    }

    public function testRepartitionTempCount(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $repartitionTempRepository = $entityManager->getRepository(RepartitionTemp::class);
        $response = $repartitionTempRepository->findBy(['setup' => self::$setup]);

        $this->assertCount(15, $response, 'Une valeur pour setup doit être trouvée.');
    }

}
