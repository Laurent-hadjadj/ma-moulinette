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

use App\Entity\OwaspTop10;
use App\DataFixtures\OwaspTop10Fixtures;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


/**
 * [Description OwaspTop10RepositoryTest]
 */
class OwaspTop10RepositoryTest extends KernelTestCase
{

    private static $erreurCode200 = 'Erreur le code retour doit être 200.';

    /**
     * [Description for setUp]
     * Création des OwaspTop10 en base depuis les fixtures
     *
     * @return void
     *
     * Created at: 05/05/2024 18:15:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.owasp_top10_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new OwaspTop10Fixtures()]);
    }

    /**
     * [Description for testSelectOwaspTop10Referential]
     *
     * @return void
     *
     * Created at: 07/07/2025 08:49:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testSelectOwaspTop10Referential(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = [ 'referential_version' => 2017 ];

        // Appel de la méthode
        $owaspTop10Repository = $entityManager->getRepository(OwaspTop10::class);
        $response = $owaspTop10Repository->selectOwaspTop10Referential($map);

        // Assert
        $this->assertEquals(200, $response['code'], static::$erreurCode200);
        $this->assertEmpty($response['erreur'], $response['erreur']);

        $liste = $response['liste'][0];
        $this->assertArrayHasKey('year', $liste);
        $this->assertArrayHasKey('category', $liste);
        $this->assertArrayHasKey('description', $liste);
        $this->assertArrayHasKey('date_enregistrement', $liste);
        $this->assertIsInt($liste['year']);
        $this->assertEquals("A1 - Attaques d'injection", $liste['category']);
    }

    public function testSelectOwaspTop10Details(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = [ 'menace' => 1 ];

        // Appel de la méthode
        $owaspTop10Repository = $entityManager->getRepository(OwaspTop10::class);
        $response = $owaspTop10Repository->selectOwaspTop10Details($map);

        // Assert
        $this->assertEquals(200, $response['code'], static::$erreurCode200);
        $this->assertEmpty($response['erreur'], $response['erreur']);

        $liste = $response['details'][0];
        $this->assertArrayHasKey('year', $liste);
        $this->assertArrayHasKey('category', $liste);
        $this->assertArrayHasKey('description', $liste);
        $this->assertArrayHasKey('date_enregistrement', $liste);
        $this->assertIsInt($liste['year']);
        $this->assertEquals("A1 - Attaques d'injection", $liste['category']);
    }

}
