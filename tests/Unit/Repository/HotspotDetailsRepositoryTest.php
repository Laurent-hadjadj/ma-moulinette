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

namespace App\Tests\Unit\Repository;

use App\Entity\HotspotDetails;
use App\DataFixtures\HotspotDetailsFixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description HotspotDetailsRepositoryTest]
 */
class HotspotDetailsRepositoryTest extends KernelTestCase
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
            $sequence = 'ma_moulinette.hotspot_details_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new HotspotDetailsFixtures()]);
    }

    public function testSelectHotspotDetailsByStatus(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $hotspotDetailsRepository = $entityManager->getRepository(HotspotDetails::class);
        $r = $hotspotDetailsRepository->selectHotspotDetailsByStatus($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteHotspotDetailsMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $hotspotDetailsRepository = $entityManager->getRepository(HotspotDetails::class);
        $r = $hotspotDetailsRepository->deleteHotspotDetailsMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertHotspotDetails(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map= [['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
        'version' => '2.0.0-RELEASE', 'date_version' => new \DateTimeImmutable('2024-08-30 10:12:17+02'),
        'security_category' => 'dos', 'rule_key' => 'typescript:S5852',
        'rule_name' => 'Using slow regular expressions is security-sensitive',
        'severity' => 'MEDIUM', 'status' => 'TO_REVIEW', 'resolution' => 'Todo',
        'niveau' => 2, 'frontend' => 1, 'backend' => 1, 'autre' => 0,
        'file_name' => 'service-worker-network-first.ts',
        'file_path' => 'ma-moulinette/angular/src/service-worker-network-first.ts',
        'line' => 60, 'message' => 'Make sure the regex used here, which is vulnerable to super-linear runtime due to backtracking, cannot lead to denial of service.', 'hotspot_key' => 'AZCc06XbgfifxdiJPzw2',
        'mode_collecte' => 'TRAITEMENT AUTOMATIQUE',
        'utilisateur_collecte' => 'laurent.hadjadj@ma-petite-entreprise.fr',
        'date_enregistrement' => new \DateTimeImmutable('2024-03-26 14:46:38+02')]];

        $hotspotDetailsRepository = $entityManager->getRepository(HotspotDetails::class);
        $r = $hotspotDetailsRepository->insertHotspotDetails($map);

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
