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

use App\Entity\Activity;
use App\DataFixtures\ActivityFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ActivityRepositoryTest]
 */
class ActivityRepositoryTest extends KernelTestCase
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
            $sequence = 'ma_moulinette.activity_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new ActivityFixtures()]);
    }

    public function testInsertSelectActivite(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->selectActivity($year);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertActivity(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=[['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
            'project_name' => 'ma-moulinette', 'analyse_id' => 'vtrf14lkiutq9mp',
            'status' => 'SUCCESS', 'submitter_login' => 'laurent.hadjadj',
            'submitted_at' => new  \DateTime('2024-07-31 12:26:58+02'),
            'started_at' => new  \DateTime('2024-07-31 12:27:05+02'),
            'executed_at' => new  \DateTime('2024-07-31 12:27:47+02'),
            'execution_time' => 42]];

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->insertActivity($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testNombreJourAnneeDonnee(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->nombreJourAnneeDonnee($year);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testTempsExecutionMax(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->tempsExecutionMax($year);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testNombreStatus(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;
        $status='SUCCESS';

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->nombreStatus($year, $status);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }


    public function testNombreAnalyse(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->nombreAnalyse($year);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDernierDate(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;
        $yearNull=null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->dernierDate($year);
        $r2 = $activityRepository->dernierDate($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testPremiereDate(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;
        $yearNull=null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->premiereDate($year);
        $r2 = $activityRepository->premiereDate($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testListeProjectAnalyse(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;
        $yearNull=null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->listeProjectAnalyse($year);
        $r2 = $activityRepository->listeProjectAnalyse($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testListeAnalyseJour(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;
        $yearNull=null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->listeAnalyseJour($year);
        $r2 = $activityRepository->listeAnalyseJour($yearNull);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testListeAnalyseProjet(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $year=2024;
        $yearNull=null;

        $activityRepository = $entityManager->getRepository(Activity::class);
        $r = $activityRepository->listeAnalyseProjet($year);
        $r2 = $activityRepository->listeAnalyseProjet($yearNull);

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
