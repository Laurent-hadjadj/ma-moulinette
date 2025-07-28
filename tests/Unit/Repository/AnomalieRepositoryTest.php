<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Anomalie;
use App\DataFixtures\AnomalieFixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description AnomalieRepositoryTest]
 */
class AnomalieRepositoryTest extends KernelTestCase
{
    private static $erreurCode200 = 'Erreur le code retour doit être 200';
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.anomalie_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new AnomalieFixtures()]);
    }

    public function testDeleteAnomalieMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map = ['maven_key' => static::$mavenKey];

        $anomalieRepository = $entityManager->getRepository(Anomalie::class);
        $r = $anomalieRepository->deleteAnomalieMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectAnomalieByProjectName(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $anomalieRepository = $entityManager->getRepository(Anomalie::class);
        $r = $anomalieRepository->selectAnomalieByProjectName();

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectAnomalie(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=['maven_key' => static::$mavenKey];

        // Appel de la méthode
        $anomalieRepository = $entityManager->getRepository(Anomalie::class);
        $r = $anomalieRepository->selectAnomalie($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertAnomalie(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => static::$mavenKey,
        'project_name' => 'ma-moulinette', 'anomalie_total' => 1956,
        'dette_minute' => 19586, 'dette_reliability_minute' => 107,
        'dette_vulnerability_minute' => 0, 'dette_code_smell_minute' => 7369,
        'dette_reliability' => '0h:5min', 'dette_vulnerability' => '0h:0min',
        'dette' => '4d, 19h:32min',  'dette_code_smell' => '5d, 2h:49min',
        'frontend' => 806, 'backend' => 0, 'autre' => 0, 'inconnue' => 1, 'blocker' => 0,
        'critical' => 0, 'major' => 4750, 'info' => 0, 'minor' => 222,
        'bug' => 0, 'vulnerability' => 0, 'code_smell' => 801,
        'mode_collecte' => 'TRAITEMENT MANUEL', 'utilisateur_collecte' => 'laurent.hadjadj@ma-petite-entreprise.fr',  'date_enregistrement' => new \DateTimeImmutable('2024-06-28 17:55:45+02')];

        // Appel de la méthode
        $anomalieRepository = $entityManager->getRepository(Anomalie::class);
        $r = $anomalieRepository->insertAnomalie($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
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
