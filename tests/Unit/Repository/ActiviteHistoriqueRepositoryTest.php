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

use App\Entity\ActiviteHistorique;
use App\DataFixtures\ActiviteHistoriqueFixtures;
use DateTime;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description ActiviteHistoriqueRepositoryTest]
 */
class ActiviteHistoriqueRepositoryTest extends KernelTestCase
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
            $sequence = 'ma_moulinette.activite_historique_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new ActiviteHistoriqueFixtures()]);
    }

    public function testInsertHistoriqueActivites(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=[ 2024 => ['nb_jour' => 326, 'nb_analyse' => 1253,
                'moyenne_analyse' => 87.3, 'nb_reussi' => 1249, 'nb_echec' => 4, 'taux_reussite' => 0.99, 'max_temps' => 34, 'date_enregistrement' => new  DateTime('2024-07-14 19:36:33+02')]];

        $activiteHistoriqueRepository = $entityManager->getRepository(ActiviteHistorique::class);
        $r = $activiteHistoriqueRepository->insertHistoriqueActivites($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testUpdateHistoriqueActivites(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $map=[ 2024 => ['nb_jour' => 326, 'nb_analyse' => 1253,
                'moyenne_analyse' => 87.3, 'nb_reussi' => 1249, 'nb_echec' => 4, 'taux_reussite' => 0.99, 'max_temps' => 34, 'date_enregistrement' => new  DateTime('2024-07-14 19:36:33+02')]];

        $activiteHistoriqueRepository = $entityManager->getRepository(ActiviteHistorique::class);
        $r = $activiteHistoriqueRepository->updateHistoriqueActivites($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testSelectActivite(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Appel de la méthode
        $annee=2024;
        $activiteHistoriqueRepository = $entityManager->getRepository(ActiviteHistorique::class);
        $r = $activiteHistoriqueRepository->selectActivite($annee);

        $annee=null;
        $r2 = $activiteHistoriqueRepository->selectActivite($annee);

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
