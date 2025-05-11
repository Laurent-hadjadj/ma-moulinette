<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Repository;

use App\Entity\ActivityHistorique;
use App\DataFixtures\ActivityHistoriqueFixtures;
use DateTime;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ActivityHistoriqueRepositoryTest]
 */
class ActivityHistoriqueRepositoryTest extends KernelTestCase
{
    private static $erreurCode200 = 'Erreur le code retour doit être 200';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.activity_historique_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new ActivityHistoriqueFixtures()]);
    }

    public function testInsertHistoriqueActivity(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map = [ 2024 => ['day' => 326, 'analyse' => 1253,
                'analyse_average' => 87.3, 'success' => 1249, 'failed' => 4, 'success_rate' => 0.99, 'max_time' => 34, 'date_enregistrement' => new  DateTime('2024-07-14 19:36:33+02')]];

        $activityHistoriqueRepository = $entityManager->getRepository(ActivityHistorique::class);
        $r = $activityHistoriqueRepository->insertHistoriqueActivity($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testUpdateHistoriqueActivity(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=[ 2024 => ['day' => 326, 'analyse' => 1253,
                'analyse_average' => 87.3, 'success' => 1249, 'failed' => 4, 'success_rate' => 0.99, 'max_time' => 34, 'date_enregistrement' => new  DateTime('2024-07-14 19:36:33+02')]];


        $activiteHistoriqueRepository = $entityManager->getRepository(ActivityHistorique::class);
        $r = $activiteHistoriqueRepository->updateHistoriqueActivity($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectActivity(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year = 2024;
        $activityHistoriqueRepository = $entityManager->getRepository(ActivityHistorique::class);
        $r = $activityHistoriqueRepository->selectActivity($year);

        $year=null;
        $r2 = $activityHistoriqueRepository->selectActivity($year);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // On se déconnecte pour éviter des problèmes de mémoires
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->close();
        $entityManager = null;
    }
}
