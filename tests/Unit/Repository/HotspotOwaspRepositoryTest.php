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

use App\Entity\HotspotOwasp;
use App\DataFixtures\HotspotOwaspFixtures;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description HotspotOwaspRepositoryTest]
 */
class HotspotOwaspRepositoryTest extends KernelTestCase
{
    private static $erreurCode200 = 'Erreur le code retour doit être 200.';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.hotspot_owasp_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new HotspotOwaspFixtures()]);
    }

    public function testCountHotspotOwaspStatus(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map1=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette', 'status' =>'TO_REVIEW'];
        $map2=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette', 'status' =>'REVIEWED'];

        $hotspotOwaspRepository = $entityManager->getRepository(HotspotOwasp::class);
        $r1 = $hotspotOwaspRepository->countHotspotOwaspStatus($map1);
        $r2 = $hotspotOwaspRepository->countHotspotOwaspStatus($map2);

        // Assert
        $this->assertEquals(200, $r1['code'], static::$erreurCode200);
        $this->assertEmpty($r1['erreur'], $r1['erreur']);
        $this->assertEquals(200, $r2['code'], static::$erreurCode200);
        $this->assertEmpty($r2['erreur'], $r2['erreur']);
    }

    public function testCountHotspotOwaspProbability(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $hotspotOwaspRepository = $entityManager->getRepository(HotspotOwasp::class);
        $r = $hotspotOwaspRepository->countHotspotOwaspProbability($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testCountHotspotOwaspMenaces(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map= ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $hotspotOwaspRepository = $entityManager->getRepository(HotspotOwasp::class);
        $r = $hotspotOwaspRepository->countHotspotOwaspMenaces($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testCountHotspotOwaspMenaceByStatus(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map= ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette', 'menace' => 'a1', 'probability' => 'MEDIUM'];

        $hotspotOwaspRepository = $entityManager->getRepository(HotspotOwasp::class);
        $r = $hotspotOwaspRepository->countHotspotOwaspMenaceByStatus($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteHotspotOwaspMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map= ['maven_key' => 'fr.ma-petite-entreprise:ma-moulinette'];

        $hotspotOwaspRepository = $entityManager->getRepository(HotspotOwasp::class);
        $r = $hotspotOwaspRepository->deleteHotspotOwaspMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertHotspotOwasp(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=  [['referentiel_owasp' => 2017, 'version' => '2.0.0-RELEASE',
                'maven_key' => 'fr.ma-petite-entreprise:ma-moulinette',
                'date_version' => new \DateTimeImmutable('2024-08-10 15:26:07+02'), 'menace' => 'a1', 'security_category' => 'dos',
                'rule_key' => 'typescript:S5852', 'probability' => 'MEDIUM',
                'status' => 'TO_REVIEW', 'resolution' => 'Todo', 'niveau' => 2,
                'mode_collecte' => 'TRAITEMENT MANUEL', 'utilisateur_collecte' => 'laurent.hadjadj@ma-petite-entreprise.fr',
                'date_enregistrement' => new \DateTimeImmutable('2024-04-12 16:23:11+01')]];
        $hotspotOwaspRepository = $entityManager->getRepository(HotspotOwasp::class);
        $r = $hotspotOwaspRepository->insertHotspotOwasp($map);

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
