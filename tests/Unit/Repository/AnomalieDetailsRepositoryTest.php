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

use App\Entity\AnomalieDetails;
use App\DataFixtures\AnomalieDetailsFixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description AnomalieDetailsRepositoryTest]
 */
class AnomalieDetailsRepositoryTest extends KernelTestCase
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
            $sequence = 'ma_moulinette.anomalie_details_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new AnomalieDetailsFixtures()]);
    }

    public function testDeleteAnomalieDetailsMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $anomalieDetailsRepository = $entityManager->getRepository(AnomalieDetails::class);
        $r = $anomalieDetailsRepository->deleteAnomalieDetailsMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectAnomalieDetailsMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();

        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $anomalieDetailsRepository = $entityManager->getRepository(AnomalieDetails::class);
        $r = $anomalieDetailsRepository->selectAnomalieDetailsMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertAnomalieDetail(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map=[
                'maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
                'name' => 'ma-moulinette', 'bug_blocker' => 7,
                'bug_critical' => 0, 'bug_major' => 44, 'bug_info' => 37,
                'bug_minor' => 0, 'vulnerability_blocker' => 0,
                'vulnerability_critical' => 9, 'vulnerability_major' => 0,
                'vulnerability_info' => 0, 'vulnerability_minor' => 0,
                'code_smell_blocker' => 0, 'code_smell_critical' => 4,
                'code_smell_major' => 109, 'code_smell_info' => 72,
                'code_smell_minor' => 13,  'utilisateur_collecte' => 'laurent.hadjadj@ma-petite-entreprise.fr', 'mode_collecte' => 'TRAITEMENT MANUEL', 'date_enregistrement' => new \DateTimeImmutable('2024-06-28 17:55:45+02')];

        // Appel de la méthode
        $anomalieDetailsRepository = $entityManager->getRepository(AnomalieDetails::class);
        $r = $anomalieDetailsRepository->insertAnomalieDetail($map);

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
